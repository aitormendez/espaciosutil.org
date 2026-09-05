-- Ejecutar exclusivamente en la base de datos de staging, con HTTP detenido.
DELETE FROM wp_options WHERE option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%' OR option_name = 'cron';
DELETE FROM wp_options WHERE option_name REGEXP '^easy_wp_smtp' OR option_name REGEXP '^(pmpro_.*stripe.*(key|secret|token|user_id|webhook)|espaciosutil_.*(token|secret)|woocommerce_.*(settings|token|secret|api_key))$';
DELETE FROM wp_usermeta WHERE meta_key IN ('session_tokens', '_application_passwords') OR meta_key REGEXP '(stripe|paypal).*(customer|token|secret)';
UPDATE wp_users SET user_activation_key = '';
UPDATE wp_options SET option_value = 'sandbox' WHERE option_name = 'pmpro_gateway_environment';
UPDATE wp_options SET option_value = '0' WHERE option_name IN ('blog_public', 'users_can_register');
UPDATE wp_options SET option_value = 'staging@example.invalid' WHERE option_name IN ('admin_email','new_admin_email');
TRUNCATE wp_actionscheduler_actions;
TRUNCATE wp_actionscheduler_claims;
TRUNCATE wp_actionscheduler_groups;
TRUNCATE wp_actionscheduler_logs;
TRUNCATE wp_easywpsmtp_debug_events;
TRUNCATE wp_easywpsmtp_tasks_meta;
TRUNCATE wp_wc_webhooks;
TRUNCATE wp_woocommerce_api_keys;
TRUNCATE wp_woocommerce_payment_tokens;
TRUNCATE wp_woocommerce_payment_tokenmeta;
TRUNCATE wp_woocommerce_sessions;
