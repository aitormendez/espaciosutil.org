<?php
// Recibe contraseñas por stdin; no las imprime ni las conserva en un archivo.
if (gethostname() !== 'espacio-sutil-staging' || !defined('WP_ENV') || WP_ENV !== 'staging' || home_url() !== 'https://stage.espaciosutil.org') {
    WP_CLI::error('Entorno inesperado.');
}
$secrets = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$admin = get_user_by('login', 'staging-admin');
if (!$admin) {
    $id = wp_create_user('staging-admin', $secrets['admin_password'], 'staging@example.invalid');
    if (is_wp_error($id)) WP_CLI::error('No se pudo crear el administrador de pruebas.');
    (new WP_User($id))->set_role('administrator');
}
foreach (['active','none','expired','cancelled'] as $kind) {
    $login = 'cde-test-' . $kind;
    $user = get_user_by('login', $login);
    $id = $user ? $user->ID : wp_create_user($login, $secrets['test_password'], $login . '@example.invalid');
    if (is_wp_error($id)) WP_CLI::error('No se pudo crear la cuenta de pruebas.');
    $account = new WP_User($id); $account->set_role('subscriber');
    // Fijar sólo los estados sintéticos, sin hooks de cancelación o pasarelas.
    global $wpdb;
    $wpdb->delete($wpdb->pmpro_memberships_users, ['user_id' => $id]);
    if ($kind !== 'none') {
        $wpdb->insert($wpdb->pmpro_memberships_users, [
            'user_id' => $id, 'membership_id' => 11,
            'status' => $kind === 'active' ? 'active' : ($kind === 'expired' ? 'expired' : 'cancelled'),
            'startdate' => gmdate('Y-m-d H:i:s', time() - 86400 * 30),
            'enddate' => $kind === 'active' ? '0000-00-00 00:00:00' : gmdate('Y-m-d H:i:s', time() - 86400),
        ]);
    }
    $has_access = (bool) pmpro_hasMembershipLevel([11,12,13], $id);
    if ($has_access !== ($kind === 'active')) WP_CLI::error('Estado de membresía inesperado.');
}
WP_CLI::success('Administrador y cuatro estados de membresía de prueba preparados.');
