"""Private stdin/stdout verifier. No HTTP listener, secrets or receipts in logs."""
import json
import sys
import time
import signal
import base64
from pathlib import Path
from datetime import datetime
from urllib.parse import quote


def timestamp(value):
    return int(datetime.fromisoformat(value.replace('Z', '+00:00')).timestamp()) if value else 0


def google_snapshot(data, reference, environment, products):
    if ('testPurchase' in data) != (environment == 'sandbox'):
        raise ValueError('environment')
    items = [x for x in data.get('lineItems', []) if x.get('productId') in products]
    # A deferred change can include the future item, which has not been purchased yet.
    if len(items) > 1:
        items = [x for x in items if x.get('latestSuccessfulOrderId') and timestamp(x.get('expiryTime')) > int(time.time())]
    if len(items) != 1:
        raise ValueError('product')
    item = items[0]
    configured = products[item['productId']]
    base = item.get('offerDetails', {}).get('basePlanId', '')
    if configured.get('base_plan_id', '') != base:
        raise ValueError('base_plan')
    state = data.get('subscriptionState')
    status = {'SUBSCRIPTION_STATE_ACTIVE': 'active', 'SUBSCRIPTION_STATE_IN_GRACE_PERIOD': 'grace',
              'SUBSCRIPTION_STATE_CANCELED': 'cancelled', 'SUBSCRIPTION_STATE_EXPIRED': 'expired',
              'SUBSCRIPTION_STATE_ON_HOLD': 'hold', 'SUBSCRIPTION_STATE_PAUSED': 'paused',
              'SUBSCRIPTION_STATE_PENDING': 'pending', 'SUBSCRIPTION_STATE_PENDING_PURCHASE_CANCELED': 'expired'}.get(state)
    if status is None:
        raise ValueError('state')
    if 'replacementCancellation' in data.get('canceledStateContext', {}):
        status = 'replaced'
    return dict(provider='google', environment=environment, reference=reference,
                linked_reference=data.get('linkedPurchaseToken') if state not in ('SUBSCRIPTION_STATE_PENDING', 'SUBSCRIPTION_STATE_PENDING_PURCHASE_CANCELED') else None, product_id=item['productId'], base_plan_id=base,
                account_token=data.get('externalAccountIdentifiers', {}).get('obfuscatedExternalAccountId'),
                status=status, expires_at=timestamp(item.get('expiryTime')),
                auto_renew=bool(item.get('autoRenewingPlan', {}).get('autoRenewEnabled')), checked_at=int(time.time()))


def verify_google(cfg, body):
    from google.oauth2 import service_account
    from google.auth.transport.requests import AuthorizedSession
    credentials = service_account.Credentials.from_service_account_file(cfg['service_account_file'], scopes=['https://www.googleapis.com/auth/androidpublisher'])
    # Credential endpoints are configured by administrators; reject custom token endpoints.
    if credentials._token_uri != 'https://oauth2.googleapis.com/token':
        raise ValueError('credential_endpoint')
    session = AuthorizedSession(credentials)
    url = 'https://androidpublisher.googleapis.com/androidpublisher/v3/applications/' + quote(cfg['package_id'], safe='') + '/purchases/subscriptionsv2/tokens/' + quote(body['reference'], safe='')
    response = session.get(url, timeout=12, allow_redirects=False)
    if response.status_code != 200:
        raise ValueError('provider')
    data = response.json()
    result = google_snapshot(data, body['reference'], cfg['environment'], cfg['products'])
    if body.get('action') == 'acknowledge' and data.get('acknowledgementState') == 'ACKNOWLEDGEMENT_STATE_PENDING':
        if result['status'] not in ('active', 'grace', 'cancelled') or result['expires_at'] <= int(time.time()):
            raise ValueError('not_purchased')
        url = 'https://androidpublisher.googleapis.com/androidpublisher/v3/applications/' + quote(cfg['package_id'], safe='') + '/purchases/subscriptions/' + quote(result['product_id'], safe='') + '/tokens/' + quote(body['reference'], safe='') + ':acknowledge'
        ack = session.post(url, json={}, timeout=12, allow_redirects=False)
        if ack.status_code not in (200, 204):
            raise ValueError('acknowledgement')
    return result


def verify_apple(cfg, body):
    from appstoreserverlibrary.api_client import AppStoreServerAPIClient
    from appstoreserverlibrary.signed_data_verifier import SignedDataVerifier
    from appstoreserverlibrary.models.Environment import Environment
    environment = Environment.SANDBOX if cfg['environment'] == 'sandbox' else Environment.PRODUCTION
    verifier = SignedDataVerifier([Path(p).read_bytes() for p in cfg['root_certificates']], True, environment, cfg['bundle_id'], cfg.get('app_apple_id'))
    client = AppStoreServerAPIClient(Path(cfg['private_key_file']).read_bytes(), cfg['key_id'], cfg['issuer_id'], cfg['bundle_id'], environment)
    transaction = verifier.verify_and_decode_signed_transaction(client.get_transaction_info(body['reference']).signedTransactionInfo)
    if transaction.productId not in cfg['products'] or transaction.subscriptionGroupIdentifier != cfg['subscription_group_id']:
        raise ValueError('product')
    result = client.get_all_subscription_statuses(transaction.originalTransactionId)
    candidates = []
    for group in result.data or []:
        for item in group.lastTransactions or []:
            tx = verifier.verify_and_decode_signed_transaction(item.signedTransactionInfo)
            renewal = verifier.verify_and_decode_renewal_info(item.signedRenewalInfo)
            if tx.originalTransactionId != transaction.originalTransactionId or tx.productId not in cfg['products']:
                continue
            if renewal.originalTransactionId != tx.originalTransactionId:
                raise ValueError('renewal')
            status = {1:'active',2:'expired',3:'hold',4:'grace',5:'revoked'}.get(item.status.value if hasattr(item.status,'value') else item.status)
            if not status:
                raise ValueError('status')
            if tx.revocationDate:
                status = 'revoked'
            expiry = (renewal.gracePeriodExpiresDate if status == 'grace' else tx.expiresDate) or 0
            candidates.append(dict(provider='apple', environment=cfg['environment'], reference=tx.originalTransactionId,
                                   linked_reference=None, product_id=tx.productId, base_plan_id='', account_token=tx.appAccountToken,
                                   status=status, expires_at=int(expiry/1000),
                                   auto_renew=renewal.autoRenewStatus == 1, checked_at=int(time.time())))
    if len(candidates) != 1:
        raise ValueError('subscription')
    return candidates[0]


def verify_notification(provider, cfg, body):
    notification = body['notification']
    if provider == 'apple':
        from appstoreserverlibrary.signed_data_verifier import SignedDataVerifier
        from appstoreserverlibrary.models.Environment import Environment
        env = Environment.SANDBOX if cfg['environment'] == 'sandbox' else Environment.PRODUCTION
        verifier = SignedDataVerifier([Path(p).read_bytes() for p in cfg['root_certificates']], True, env, cfg['bundle_id'], cfg.get('app_apple_id'))
        decoded = verifier.verify_and_decode_notification(notification['signedPayload'])
        if getattr(decoded.notificationType, 'value', decoded.notificationType) == 'TEST':
            return {'reference': ''}
        tx = verifier.verify_and_decode_signed_transaction(decoded.data.signedTransactionInfo)
        if tx.productId not in cfg['products']:
            raise ValueError('product')
        return {'reference': tx.originalTransactionId}
    from google.oauth2.id_token import verify_oauth2_token
    from google.auth.transport.requests import Request
    header = body.get('authorization', '')
    if not header.startswith('Bearer '):
        raise ValueError('authorization')
    claims = verify_oauth2_token(header[7:], Request(), cfg['notification_audience'])
    if claims.get('email') != cfg['notification_service_account'] or claims.get('email_verified') is not True:
        raise ValueError('sender')
    data = json.loads(base64.b64decode(notification['message']['data'], validate=True))
    if data.get('packageName') != cfg['package_id']:
        raise ValueError('package')
    if 'testNotification' in data:
        return {'reference': ''}
    return {'reference': data['subscriptionNotification']['purchaseToken']}


def main():
    signal.alarm(25)
    body = json.loads(sys.stdin.read(32769))
    cfg = json.loads(Path(sys.argv[1]).read_text())
    provider = body['provider']
    selected = cfg[provider]
    if selected.get('enabled') is not True or selected.get('environment') not in ('sandbox','production'):
        raise ValueError('disabled')
    # The caller derives the expected environment from WordPress, never from the user.
    if selected['environment'] != body['environment']:
        raise ValueError('environment')
    if body.get('action') == 'notification':
        print(json.dumps(verify_notification(provider, selected, body)))
        return
    if body.get('action', 'verify') not in ('verify', 'acknowledge'):
        raise ValueError('action')
    result = verify_apple(selected, body) if provider == 'apple' else verify_google(selected, body) if provider == 'google' else None
    if result is None:
        raise ValueError('provider')
    print(json.dumps(result))


if __name__ == '__main__':
    try:
        main()
    except Exception:
        print(json.dumps({'error':'verification_unavailable'}))
        sys.exit(1)
