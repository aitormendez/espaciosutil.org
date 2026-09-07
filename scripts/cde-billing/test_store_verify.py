import unittest
import copy
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
class UntrustedNotifications(unittest.TestCase):
    def test_google_requires_signed_identity(self):
        with self.assertRaises(ValueError):verify_notification('google',{}, {'notification':{},'authorization':''})
    def test_apple_rejects_unsigned_payload(self):
        # This invokes Apple's real JWS verifier, not a mocked cryptographic check.
        from appstoreserverlibrary.signed_data_verifier import VerificationException
        with self.assertRaises(VerificationException):verify_notification('apple',{'root_certificates':[],'environment':'sandbox','bundle_id':'org.example.synthetic'}, {'notification':{'signedPayload':'unsigned'}})
if __name__=='__main__':unittest.main()
