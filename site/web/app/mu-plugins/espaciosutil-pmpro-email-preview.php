<?php

/**
 * Plugin Name: Espacio Sutil PMPro Email Preview
 * Description: Añade una pantalla de previsualización para emails transaccionales de PMPro.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Only expose the preview UI in local development for administrators.
 */
function espaciosutil_pmpro_email_preview_is_available(): bool
{
    return defined('WP_ENV')
        && WP_ENV === 'development'
        && is_admin()
        && current_user_can('manage_options');
}

/**
 * Register the PMPro email preview screen under Tools.
 */
function espaciosutil_pmpro_register_pmpro_email_preview_page(): void
{
    if (!espaciosutil_pmpro_email_preview_is_available()) {
        return;
    }

    add_management_page(
        'Previsualizar emails PMPro',
        'Emails PMPro',
        'manage_options',
        'espaciosutil-pmpro-email-preview',
        'espaciosutil_pmpro_render_email_preview_page'
    );
}
add_action('admin_menu', 'espaciosutil_pmpro_register_pmpro_email_preview_page');

/**
 * Get recent PMPro orders for preview links.
 *
 * @return array<int, object>
 */
function espaciosutil_pmpro_get_recent_preview_orders(): array
{
    global $wpdb;

    return $wpdb->get_results(
        "SELECT id, code, user_id, membership_id, status, timestamp
        FROM {$wpdb->pmpro_membership_orders}
        ORDER BY id DESC
        LIMIT 10"
    ) ?: [];
}

/**
 * Resolve a PMPro order from the current preview request.
 */
function espaciosutil_pmpro_get_preview_order(): ?MemberOrder
{
    $order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
    $order_code = isset($_GET['order_code']) ? sanitize_text_field(wp_unslash($_GET['order_code'])) : '';

    $order = new MemberOrder();

    if ($order_id > 0) {
        $loaded = $order->getMemberOrderByID($order_id);
    } elseif ($order_code !== '') {
        $loaded = $order->getMemberOrderByCode($order_code);
    } else {
        $recent_orders = espaciosutil_pmpro_get_recent_preview_orders();
        $latest_order = $recent_orders[0] ?? null;
        $loaded = !empty($latest_order->id) ? $order->getMemberOrderByID((int) $latest_order->id) : false;
    }

    return $loaded && !empty($order->id) ? $order : null;
}

/**
 * Map template slugs to PMPro email template classes.
 *
 * @return array<string, string>
 */
function espaciosutil_pmpro_get_previewable_email_templates(): array
{
    return [
        'checkout_paid' => PMPro_Email_Template_Checkout_Paid::class,
        'checkout_paid_admin' => PMPro_Email_Template_Checkout_Paid_Admin::class,
        'invoice' => PMPro_Email_Template_Invoice::class,
    ];
}

/**
 * Build the isolated preview URL for an email template and order.
 */
function espaciosutil_pmpro_get_email_preview_frame_url(string $template_slug, int $order_id): string
{
    return add_query_arg(
        [
            'action' => 'espaciosutil_pmpro_email_preview_frame',
            'template' => $template_slug,
            'order_id' => $order_id,
        ],
        admin_url('admin-post.php')
    );
}

/**
 * Render a PMPro email template to HTML without sending it.
 *
 * @return array{subject:string,recipient:string,html:string}|null
 */
function espaciosutil_pmpro_render_preview_email(string $template_slug, MemberOrder $order): ?array
{
    $templates = espaciosutil_pmpro_get_previewable_email_templates();
    $template_class = $templates[$template_slug] ?? null;
    if ($template_class === null || !class_exists($template_class)) {
        return null;
    }

    $user = get_user_by('id', (int) $order->user_id);
    if (!$user instanceof WP_User) {
        return null;
    }

    $captured = [
        'subject' => '',
        'recipient' => '',
        'html' => '',
    ];

    $intercept = static function ($return, $atts) use (&$captured) {
        if (is_array($atts)) {
            $captured['subject'] = (string) ($atts['subject'] ?? '');
            $captured['recipient'] = is_array($atts['to'] ?? null)
                ? implode(', ', $atts['to'])
                : (string) ($atts['to'] ?? '');
            $captured['html'] = (string) ($atts['message'] ?? '');
        }

        return true;
    };

    add_filter('pre_wp_mail', $intercept, 10, 2);

    try {
        $email = new $template_class($user, $order);
        $email->send();
    } finally {
        remove_filter('pre_wp_mail', $intercept, 10);
    }

    return $captured['html'] !== '' ? $captured : null;
}

/**
 * Render the email preview in an isolated document so wp-admin CSS does not leak in.
 */
function espaciosutil_pmpro_render_email_preview_frame(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para acceder a esta vista previa.');
    }

    $template_slug = isset($_GET['template']) ? sanitize_key(wp_unslash($_GET['template'])) : 'checkout_paid';
    $order = espaciosutil_pmpro_get_preview_order();
    $preview = $order instanceof MemberOrder
        ? espaciosutil_pmpro_render_preview_email($template_slug, $order)
        : null;

    if ($preview === null) {
        status_header(404);
        header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>

        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Vista previa no disponible</title>
            <style>
                body {
                    margin: 0;
                    padding: 24px;
                    font-family: Arial, sans-serif;
                    background: #150b17;
                    color: #c7c3c3;
                }
            </style>
        </head>

        <body>
            <p>No se ha podido generar la vista previa para esa combinación de plantilla y pedido.</p>
        </body>

        </html>
    <?php
        exit;
    }

    header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>

    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo esc_html($preview['subject']); ?></title>
        <style>
            html,
            body {
                margin: 0;
                padding: 0;
                background: #150b17;
            }
        </style>
    </head>

    <body>
        <?php echo $preview['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
        ?>
    </body>

    </html>
<?php
    exit;
}
add_action('admin_post_espaciosutil_pmpro_email_preview_frame', 'espaciosutil_pmpro_render_email_preview_frame');

/**
 * Render the admin page with preview controls and output.
 */
function espaciosutil_pmpro_render_email_preview_page(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para acceder a esta página.');
    }

    $template_slug = isset($_GET['template']) ? sanitize_key(wp_unslash($_GET['template'])) : 'checkout_paid';
    $order = espaciosutil_pmpro_get_preview_order();
    $preview = $order instanceof MemberOrder
        ? espaciosutil_pmpro_render_preview_email($template_slug, $order)
        : null;
    $templates = espaciosutil_pmpro_get_previewable_email_templates();
    $recent_orders = espaciosutil_pmpro_get_recent_preview_orders();

?>
    <div class="wrap">
        <h1>Previsualizar emails de PMPro</h1>
        <p>Render real de la plantilla, con datos de un pedido existente y sin enviar correo.</p>

        <form method="get" action="">
            <input type="hidden" name="page" value="espaciosutil-pmpro-email-preview" />
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="template">Plantilla</label></th>
                    <td>
                        <select name="template" id="template">
                            <?php foreach (array_keys($templates) as $slug) { ?>
                                <option value="<?php echo esc_attr($slug); ?>" <?php selected($template_slug, $slug); ?>>
                                    <?php echo esc_html($slug); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="order_id">ID de pedido</label></th>
                    <td>
                        <input type="number" class="regular-text" name="order_id" id="order_id" value="<?php echo esc_attr($order->id ?? ''); ?>" />
                        <p class="description">Si lo dejas vacío, se usa el pedido más reciente.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Previsualizar'); ?>
        </form>

        <?php if (!empty($recent_orders)) { ?>
            <h2>Pedidos recientes</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Usuario</th>
                        <th>Nivel</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $recent_order) { ?>
                        <tr>
                            <td><?php echo esc_html((string) $recent_order->id); ?></td>
                            <td><?php echo esc_html((string) $recent_order->code); ?></td>
                            <td><?php echo esc_html((string) $recent_order->user_id); ?></td>
                            <td><?php echo esc_html((string) $recent_order->membership_id); ?></td>
                            <td><?php echo esc_html((string) $recent_order->status); ?></td>
                            <td>
                                <?php foreach (array_keys($templates) as $slug) { ?>
                                    <a
                                        href="<?php echo esc_url(add_query_arg([
                                                    'page' => 'espaciosutil-pmpro-email-preview',
                                                    'template' => $slug,
                                                    'order_id' => (int) $recent_order->id,
                                                ], admin_url('tools.php'))); ?>">
                                        <?php echo esc_html($slug); ?>
                                    </a>
                                    <?php if ($slug !== array_key_last($templates)) { ?>
                                        |
                                    <?php } ?>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>

        <?php if ($preview !== null && $order instanceof MemberOrder) { ?>
            <h2>Vista previa</h2>
            <p>
                <strong>Pedido:</strong> #<?php echo esc_html($order->code); ?><br />
                <strong>Destinatario:</strong> <?php echo esc_html($preview['recipient']); ?><br />
                <strong>Asunto:</strong> <?php echo esc_html($preview['subject']); ?>
            </p>
            <p>
                <a
                    href="<?php echo esc_url(espaciosutil_pmpro_get_email_preview_frame_url($template_slug, (int) $order->id)); ?>"
                    target="_blank"
                    rel="noopener noreferrer">
                    Abrir la vista previa aislada en una pestaña nueva
                </a>
            </p>
            <iframe
                title="Vista previa aislada del email"
                src="<?php echo esc_url(espaciosutil_pmpro_get_email_preview_frame_url($template_slug, (int) $order->id)); ?>"
                style="display:block;width:100%;min-height:1600px;border:1px solid #dcdcde;background:#150b17;"></iframe>
        <?php } elseif ($order === null) { ?>
            <div class="notice notice-warning">
                <p>No se ha encontrado ningún pedido para previsualizar.</p>
            </div>
        <?php } else { ?>
            <div class="notice notice-error">
                <p>No se ha podido generar la vista previa para esa combinación de plantilla y pedido.</p>
            </div>
        <?php } ?>
    </div>
<?php
}
