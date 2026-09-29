import unittest
import copy
import json
import base64
import time
from unittest.mock import patch
from store_verify import google_snapshot, verify_apple, verify_notification
PRODUCTS = {'synthetic.month': {'level_id': 11, 'base_plan_id': 'synthetic-month'}}
BASE = {'testPurchase': {}, 'subscriptionState': 'SUBSCRIPTION_STATE_ACTIVE', 'lineItems': [{'productId': 'synthetic.month', 'expiryTime': '2099-01-01T00:00:00Z', 'offerDetails': {'basePlanId': 'synthetic-month'}, 'autoRenewingPlan': {'autoRenewEnabled': True}, 'latestSuccessfulOrderId': 'synthetic-order'}], 'externalAccountIdentifiers': {'obfuscatedExternalAccountId': 'synthetic-account'}}
class GoogleSnapshots(unittest.TestCase):
    def test_states(self):
        for state, expected in [('ACTIVE','active'),('IN_GRACE_PERIOD','grace'),('CANCELED','cancelled'),('ON_HOLD','hold'),('PAUSED','paused'),('PENDING','pending'),('EXPIRED','expired'),('PENDING_PURCHASE_CANCELED','expired')]:
            d=copy.deepcopy(BASE);d['subscriptionState']='SUBSCRIPTION_STATE_'+state
            self.assertEqual(google_snapshot(d,'token','sandbox',PRODUCTS)['status'],expected)
    def test_wrong_environment(self):
        with self.assertRaises(ValueError): google_snapshot(BASE,'token','production',PRODUCTS)
        d=copy.deepcopy(BASE);del d['testPurchase']
        with self.assertRaises(ValueError): google_snapshot(d,'token','sandbox',PRODUCTS)
    def test_unknown_product_base_and_state(self):
        for change in ('product','base','state'):
            d=copy.deepcopy(BASE)
            if change=='product':d['lineItems'][0]['productId']='other'
            if change=='base':d['lineItems'][0]['offerDetails']['basePlanId']='other'
            if change=='state':d['subscriptionState']='new-unknown-state'
            with self.assertRaises(ValueError):google_snapshot(d,'token','sandbox',PRODUCTS)
    def test_future_deferred_item_not_granted(self):
        d=copy.deepcopy(BASE);future=copy.deepcopy(d['lineItems'][0]);future.pop('latestSuccessfulOrderId');d['lineItems'].append(future)
        self.assertEqual(google_snapshot(d,'token','sandbox',PRODUCTS)['product_id'],'synthetic.month')
    def test_pending_replacement_does_not_revoke_old_purchase(self):
        for state in ('PENDING','PENDING_PURCHASE_CANCELED'):
            d=copy.deepcopy(BASE);d['linkedPurchaseToken']='old';d['subscriptionState']='SUBSCRIPTION_STATE_'+state
            self.assertIsNone(google_snapshot(d,'token','sandbox',PRODUCTS)['linked_reference'])
    def test_active_replacement_invalidates_old_reference(self):
        d=copy.deepcopy(BASE);d['linkedPurchaseToken']='old';self.assertEqual(google_snapshot(d,'new','sandbox',PRODUCTS)['linked_reference'],'old')
        d['canceledStateContext']={'replacementCancellation':{}};self.assertEqual(google_snapshot(d,'old','sandbox',PRODUCTS)['status'],'replaced')
    def test_pending_without_expiry_is_not_fabricated(self):
        d=copy.deepcopy(BASE);d['subscriptionState']='SUBSCRIPTION_STATE_PENDING';d['lineItems'][0].pop('expiryTime')
        self.assertEqual(google_snapshot(d,'token','sandbox',PRODUCTS)['expires_at'],0)
class SharedTrialSnapshots(unittest.TestCase):
    def snapshot(self, state='ACTIVE', started=True, offer='prueba-7-dias'):
        d=copy.deepcopy(BASE)
        d['subscriptionState']='SUBSCRIPTION_STATE_'+state
        d['lineItems'][0]['offerDetails']['offerId']=offer
        if started:d['startTime']='2026-09-01T00:00:00Z'
        products=copy.deepcopy(PRODUCTS);products['synthetic.month']['trial_offer_id']='prueba-7-dias'
        return google_snapshot(d,'synthetic-trial','sandbox',products)
    def test_trial_is_preserved_after_renewal_cancellation_and_expiry(self):
        for state in ('ACTIVE','IN_GRACE_PERIOD','CANCELED','EXPIRED','ON_HOLD','PAUSED'):
            s=self.snapshot(state);self.assertTrue(s['trial_used']);self.assertEqual(s['offer_id'],'prueba-7-dias')
    def test_pending_and_cancelled_pending_never_consume_trial(self):
        for state in ('PENDING','PENDING_PURCHASE_CANCELED'):
            self.assertFalse(self.snapshot(state,False)['trial_used'])
    def test_wrong_offer_and_unproven_trial_start_are_rejected(self):
        with self.assertRaises(ValueError):self.snapshot(offer='unauthorized')
        with self.assertRaises(ValueError):self.snapshot(started=False)
    def test_base_plan_is_not_a_trial(self):
        self.assertFalse(self.snapshot(offer='')['trial_used'])
class UntrustedNotifications(unittest.TestCase):
    def test_google_requires_signed_identity(self):
        with self.assertRaises(ValueError):verify_notification('google',{}, {'notification':{},'authorization':''})
    def test_apple_rejects_unsigned_payload(self):
        # This invokes Apple's real JWS verifier, not a mocked cryptographic check.
        from appstoreserverlibrary.signed_data_verifier import VerificationException
        with self.assertRaises(VerificationException):verify_notification('apple',{'root_certificates':[],'environment':'sandbox','bundle_id':'org.example.synthetic'}, {'notification':{'signedPayload':'unsigned'}})
class GoogleNotificationRouting(unittest.TestCase):
    cfg = {'package_id': 'org.example.synthetic', 'notification_audience': 'https://example.invalid/notice', 'notification_service_account': 'synthetic@example.invalid'}
    def notice(self, data, claims=None):
        body = {'authorization': 'Bearer synthetic', 'notification': {'message': {'data': base64.b64encode(json.dumps(data).encode()).decode()}}}
        identity = claims or {'email': self.cfg['notification_service_account'], 'email_verified': True}
        with patch('google.oauth2.id_token.verify_oauth2_token', return_value=identity) as verify:
            result = verify_notification('google', self.cfg, body)
            self.assertEqual(verify.call_args.args[2], self.cfg['notification_audience'])
            return result
    def test_subscription_and_voided_events_trigger_recheck(self):
        for event in [{'subscriptionNotification': {'purchaseToken': 'synthetic'}}, {'voidedPurchaseNotification': {'productType': 1, 'purchaseToken': 'synthetic'}}]:
            self.assertEqual(self.notice({'packageName': self.cfg['package_id'], **event}), {'reference': 'synthetic'})
    def test_test_and_one_time_voided_do_not_grant_access(self):
        for event in [{'testNotification': {}}, {'voidedPurchaseNotification': {'productType': 2, 'purchaseToken': 'synthetic'}}]:
            self.assertEqual(self.notice({'packageName': self.cfg['package_id'], **event}), {'reference': ''})
    def test_wrong_sender_or_package_rejected(self):
        data = {'packageName': self.cfg['package_id'], 'testNotification': {}}
        for identity in [{'email': 'other@example.invalid', 'email_verified': True}, {'email': self.cfg['notification_service_account'], 'email_verified': False}]:
            with self.assertRaises(ValueError): self.notice(data, identity)
        with self.assertRaises(ValueError): self.notice({**data, 'packageName': 'other'})
    def test_unknown_voided_type_rejected(self):
        with self.assertRaises(ValueError): self.notice({'packageName': self.cfg['package_id'], 'voidedPurchaseNotification': {'productType': 999, 'purchaseToken': 'synthetic'}})

if __name__=='__main__':unittest.main()
