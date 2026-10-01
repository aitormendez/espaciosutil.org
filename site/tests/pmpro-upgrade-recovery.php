<?php

/** Execute the real recovery implementation against in-memory DB/gateway doubles. */
declare(strict_types=1);

namespace Stripe {
    class Stripe
    {
        public static function setApiKey($key) {}
    }
    class PaymentIntent
    {
        public static function retrieve($args)
        {
            return (object) ['latest_charge' => (object) ['id' => 'ch_synthetic']];
        }
    }
    class Subscription
    {
        public static function retrieve($args)
        {
            return (object) ['latest_invoice' => (object) ['id' => 'in_synthetic']];
        }
    }
}

namespace Stripe\Checkout {
    class Session
    {
        public static function retrieve($id)
        {
            $GLOBALS['gateway_reads']++;
            return match ($GLOBALS['scenario']) {
                'failed' => null,
                'payment' => (object) ['mode' => 'payment', 'payment_intent' => 'pi_synthetic'],
                'subscription' => (object) ['mode' => 'subscription', 'subscription' => 'sub_synthetic'],
                default => (object) ['mode' => 'payment'],
            };
        }
    }
}

namespace {
    class PMProGateway_stripe
    {
        public static function using_api_keys()
        {
            return true;
        }
    }
    class PMPro_Action_Scheduler
    {
        public static function instance()
        {
            return new self();
        }
        public function maybe_add_task(...$args)
        {
            $GLOBALS['scheduled'][] = $args;
        }
    }
    class PMPro_Subscription
    {
        public static function get_subscription_from_subscription_transaction_id(...$args)
        {
            return null;
        }
        public static function create($data)
        {
            $GLOBALS['subscriptions'][] = $data;
        }
    }
    class MemberOrder
    {
        public $id;
        public $gateway_environment = 'sandbox';
        public $payment_transaction_id = '';
        public $subscription_transaction_id = '';
        public $notes = '';
        public $user_id = 101;
        public $membership_id = 11;
        public $timestamp = 1790683200;
        public function __construct($id)
        {
            $this->id = $id;
        }
        public function add_order_note($note)
        {
            $this->notes .= $note;
        }
        public function saveOrder()
        {
            throw new \RuntimeException('Order hooks must not run during recovery');
        }
    }
    class FakeDB
    {
        public $pmpro_membership_orders = 'synthetic_orders';
        public $pmpro_membership_ordermeta = 'synthetic_meta';
        public $candidate = true;
        public function get_var($sql)
        {
            return $this->candidate ? 1 : null;
        }
        public function get_col($sql)
        {
            return isset($GLOBALS['metadata'][1]) ? [] : [1];
        }
        public function prepare($sql, ...$args)
        {
            return $sql;
        }
        public function update($table, $data, ...$args)
        {
            $GLOBALS['writes'][] = $data;
        }
    }
    function get_option($name)
    {
        return $GLOBALS['scenario'] === 'no_credentials' ? '' : 'synthetic-never-sent';
    }
    function get_pmpro_membership_order_meta(...$args)
    {
        return 'cs_synthetic';
    }
    function update_pmpro_membership_order_meta($id, $key, $value)
    {
        $GLOBALS['metadata'][$id] = $value;
    }
    function add_action($name, $callback)
    {
        $GLOBALS['actions'][$name][] = $callback;
    }
    function __($text, $domain = '')
    {
        return $text;
    }
    function date_i18n($format, $timestamp)
    {
        return gmdate($format, $timestamp);
    }
    function do_action(...$args)
    {
        throw new \RuntimeException('Unexpected event');
    }
    function wp_mail(...$args)
    {
        throw new \RuntimeException('Unexpected email');
    }
    function check($value, $message)
    {
        if (!$value) {
            throw new \RuntimeException($message);
        }
    }
    require __DIR__ . '/../web/app/plugins/paid-memberships-pro/includes/updates/upgrade_3_8_4.php';
    $wpdb = new FakeDB();
    $actions = [];
    $wpdb->candidate = false;
    pmpro_upgrade_3_8_4();
    check(!$actions, 'No candidate must not schedule');
    $wpdb->candidate = true;
    pmpro_upgrade_3_8_4();
    check(isset($actions['action_scheduler_init']), 'Candidate must defer scheduling');
    foreach (['no_credentials', 'failed', 'empty', 'payment', 'subscription'] as $scenario) {
        $metadata = $writes = $subscriptions = $scheduled = [];
        $gateway_reads = 0;
        pmpro_stripe_recover_checkout_transaction_ids();
        $expected = ['no_credentials' => 'no_credentials', 'failed' => 'failed', 'empty' => 'nothing_to_recover', 'payment' => 'recovered', 'subscription' => 'recovered'][$scenario];
        check($metadata[1] === $expected, 'Recovery outcome mismatch');
        check(count($writes) === (in_array($scenario, ['failed', 'payment', 'subscription'], true) ? 1 : 0), 'Unexpected DB write');
        check(count($subscriptions) === ($scenario === 'subscription' ? 1 : 0), 'Unexpected subscription creation');
        check($gateway_reads === ($scenario === 'no_credentials' ? 0 : 1), 'Unexpected gateway read');
        if ($scenario === 'payment') {
            check($writes[0]['payment_transaction_id'] === 'ch_synthetic', 'Payment ID not recovered');
        }
        if ($scenario === 'subscription') {
            check($writes[0]['subscription_transaction_id'] === 'sub_synthetic', 'Subscription ID not recovered');
        }
        $before = count($scheduled);
        pmpro_stripe_recover_checkout_transaction_ids();
        check(count($scheduled) === $before, 'Marked fixtures must terminate the chain');
    }
    echo "PASS: recovery scheduling, five outcomes and repeat exclusion; mocked Stripe only, zero events\n";
}
