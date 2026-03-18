<?php

/**
 * Theme setup.
 */

namespace App;

use Illuminate\Support\Facades\Vite;
use App\Api\CompletedLessons;
use App\Api\LessonQuiz;
use App\Api\VideoProgress;

/**
 * Inject styles into the block editor.
 *
 * @return array
 */
add_filter('block_editor_settings_all', function ($settings) {
    $style = Vite::asset('resources/css/editor.css');

    $settings['styles'][] = [
        'css' => "@import url('{$style}')",
    ];

    return $settings;
});

/**
 * Inject scripts into the block editor.
 *
 * @return void
 */
add_filter('admin_head', function () {
    if (! get_current_screen()?->is_block_editor()) {
        return;
    }

    $dependencies = json_decode(Vite::content('editor.deps.json'));

    foreach ($dependencies as $dependency) {
        if (! wp_script_is($dependency)) {
            wp_enqueue_script($dependency);
        }
    }

    echo Vite::withEntryPoints([
        'resources/js/editor.js',
    ])->toHtml();
});

/**
 * Use the generated theme.json file.
 *
 * @return string
 */
add_filter('theme_file_path', function ($path, $file) {
    return $file === 'theme.json'
        ? public_path('build/assets/theme.json')
        : $path;
}, 10, 2);

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Disable full-site editing support.
     *
     * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'sage'),
        'membresia_navigation' => __('Membresía Navigation', 'sage'),
        'cde_navigation' => __('CDE Navigation', 'sage'),
    ]);

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * Enable excerpts on pages (used by the membership landing hero subtitle).
     */
    add_post_type_support('page', 'excerpt');

    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');

    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#customize-selective-refresh-widgets
     */
    add_theme_support('customize-selective-refresh-widgets');
}, 20);

/**
 * Register REST API endpoints.
 *
 * @return void
 */
add_action('rest_api_init', function () {
    $video_progress_api = new VideoProgress();
    $video_progress_api->register_routes();

    $completed_lessons_api = new CompletedLessons();
    $completed_lessons_api->register_routes();

    $lesson_quiz_api = new LessonQuiz();
    $lesson_quiz_api->register_routes();
});

/**
 * Enqueue theme scripts and localize data.
 *
 * @return void
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('sage/app.js', Vite::asset('resources/js/app.js'), [], null, true);

    wp_localize_script(
        'sage/app.js',
        'wpApiSettings',
        [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ]
    );
});

add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if ($handle === 'sage/app.js' && ! str_contains($tag, 'type=')) {
        $tag = str_replace('<script ', '<script type="module" ', $tag);
    }

    return $tag;
}, 10, 3);

/**
 * Permite que Paid Memberships Pro muestre el metabox de niveles
 * en las lecciones del CPT `cde` y respete sus restricciones en búsquedas.
 */
add_filter('pmpro_restrictable_post_types', function ($postTypes) {
    $postTypes = is_array($postTypes) ? $postTypes : (array) $postTypes;
    $postTypes[] = 'cde';

    return array_values(array_unique($postTypes));
});

// Nota: no añadimos 'cde' a 'pmpro_search_filter_post_types' para que
// los listados/índices del curso muestren todas las lecciones.

/**
 * HTML Forms: captcha simple para el formulario de contacto.
 */
add_filter('hf_validate_form', function ($error, $form, $data) {
    if (($form->slug ?? '') !== 'contacto') {
        return $error;
    }

    if (! empty($error)) {
        return $error;
    }

    $answer = strtolower(trim($data['CAPTCHA'] ?? ''));
    if ($answer !== 'luz') {
        return 'invalid_captcha';
    }

    $privacyAccepted = ! empty($data['PRIVACY_CONSENT']);
    if (! $privacyAccepted) {
        return 'invalid_privacy_consent';
    }

    return '';
}, 10, 3);

add_filter('hf_form_response', function ($response, $form, $data) {
    if (($form->slug ?? '') !== 'contacto') {
        return $response;
    }

    if (isset($response['message']['type']) && $response['message']['type'] === 'error') {
        $response['message']['text'] = __('No pudimos enviar el formulario. Revisa la prueba de seguridad y la aceptación de privacidad.', 'sage');
    }

    return $response;
}, 10, 3);

// HTML Forms: permitir campos extra (p. ej. antispam) sin marcar como spam por tamaño del POST.
add_filter('hf_validate_form_request_size', '__return_false');

/**
 * PMPro: personalizar el texto de aceptación de condiciones.
 */
add_filter('pmpro_tos_field_label', function ($label, $tospage) {
    if (! is_object($tospage) || empty($tospage->ID)) {
        return $label;
    }

    return sprintf(
        __('He leído y acepto las <a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>.', 'sage'),
        esc_url(get_permalink((int) $tospage->ID)),
        esc_html(get_the_title((int) $tospage->ID))
    );
}, 10, 2);

/**
 * PMPro: checkbox expreso para inicio inmediato del contenido digital.
 */
add_action('pmpro_checkout_before_submit_button', function ($level = null) {
    global $pmpro_review;

    if (! empty($pmpro_review)) {
        return;
    }

    $checked = ! empty($_REQUEST['legal_immediate_access']);
    ?>
    <fieldset id="pmpro_legal_immediate_access"
        class="<?php echo esc_attr(pmpro_get_element_class('pmpro_form_fieldset', 'pmpro_legal_immediate_access')); ?>">
        <div class="<?php echo esc_attr(pmpro_get_element_class('pmpro_form_fields')); ?>">
            <div
                class="<?php echo esc_attr(pmpro_get_element_class('pmpro_form_field pmpro_form_field-checkbox pmpro_form_field-required')); ?>">
                <label class="<?php echo esc_attr(pmpro_get_element_class('pmpro_form_label pmpro_clickable', 'legal_immediate_access')); ?>"
                    for="legal_immediate_access">
                    <input type="checkbox" name="legal_immediate_access" value="1" id="legal_immediate_access"
                        <?php checked(true, $checked); ?>
                        class="<?php echo esc_attr(pmpro_get_element_class('pmpro_form_input pmpro_form_input-checkbox pmpro_form_input-required', 'legal_immediate_access')); ?>" />
                    <?php esc_html_e('Solicito el acceso inmediato al contenido digital y soy consciente de que, una vez iniciada la ejecución del servicio, puedo perder mi derecho de desistimiento en los términos legalmente aplicables.', 'sage'); ?>
                </label>
            </div>
        </div>
    </fieldset>
    <?php
}, 6);

/**
 * PMPro: validar la aceptación del inicio inmediato del servicio digital.
 */
$validateImmediateAccessConsent = function ($pmproContinueRegistration) {
    global $pmpro_error_fields;

    if (! $pmproContinueRegistration) {
        return $pmproContinueRegistration;
    }

    if (! isset($_REQUEST['legal_immediate_access']) || empty($_REQUEST['legal_immediate_access'])) {
        $pmpro_error_fields[] = 'legal_immediate_access';
        pmpro_setMessage(
            __('Debes aceptar el inicio inmediato del contenido digital para continuar con la suscripción.', 'sage'),
            'pmpro_error'
        );

        return false;
    }

    return $pmproContinueRegistration;
};

add_filter('pmpro_checkout_user_creation_checks', $validateImmediateAccessConsent);
add_filter('pmpro_checkout_order_creation_checks', $validateImmediateAccessConsent);

/**
 * Return core PMPro page IDs configured in options.
 */
function pmpro_core_page_ids(): array
{
    static $ids = null;

    if (is_array($ids)) {
        return $ids;
    }

    $optionKeys = [
        'pmpro_account_page_id',
        'pmpro_billing_page_id',
        'pmpro_cancel_page_id',
        'pmpro_checkout_page_id',
        'pmpro_confirmation_page_id',
        'pmpro_invoice_page_id',
        'pmpro_levels_page_id',
        'pmpro_login_page_id',
        'pmpro_member_profile_edit_page_id',
    ];

    $ids = array_filter(array_map('intval', array_map('get_option', $optionKeys)));

    return $ids;
}

/**
 * Check whether a given page ID belongs to PMPro core pages.
 */
function is_pmpro_core_page(int $pageId = 0): bool
{
    if ($pageId <= 0) {
        return false;
    }

    return in_array($pageId, pmpro_core_page_ids(), true);
}

/**
 * Normalize a request path so cache-sensitive prefixes are easy to match.
 */
function normalize_fastcgi_cache_path(?string $path): string
{
    $path = is_string($path) ? trim($path) : '';

    if ($path === '') {
        return '/';
    }

    $normalized = '/' . ltrim($path, '/');

    return str_ends_with($normalized, '/') ? $normalized : "{$normalized}/";
}

/**
 * Detect frontend membership routes that should never be cached.
 */
function is_fastcgi_uncacheable_membership_request(): bool
{
    $requestPath = wp_parse_url(
        (string) wp_unslash($_SERVER['REQUEST_URI'] ?? ''),
        PHP_URL_PATH
    );

    $normalizedPath = normalize_fastcgi_cache_path(is_string($requestPath) ? $requestPath : '');
    $sensitivePrefixes = [
        '/login/',
        '/cuenta-de-membresia/',
        '/pago-de-membresia/',
        '/confirmacion-de-membresia/',
    ];

    foreach ($sensitivePrefixes as $prefix) {
        if (str_starts_with($normalizedPath, $prefix)) {
            return true;
        }
    }

    return false;
}

add_action('send_headers', function () {
    if (is_admin()) {
        return;
    }

    $pageId = (int) get_queried_object_id();
    if (! is_pmpro_core_page($pageId) && ! is_fastcgi_uncacheable_membership_request()) {
        return;
    }

    // Trellis respects Cache-Control, so this keeps auth and PMPro flows out of FastCGI cache.
    nocache_headers();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
}, 0);

/**
 * Resolve runtime URLs used inside PMPro account messages.
 */
function cde_membership_placeholder_links(): array
{
    $accountUrl = function_exists('pmpro_url')
        ? pmpro_url('account')
        : home_url('/cuenta-de-membresia/');

    $links = [
        'ENLACE_HUB' => [
            'href' => get_permalink((int) (get_page_by_path('curso-de-desarrollo-espiritual')?->ID ?? 0))
                ?: home_url('/curso-de-desarrollo-espiritual/'),
            'label' => __('Ir al hub', 'sage'),
        ],
        'ENLACE_INDICE_DE_LECCIONES' => [
            'href' => get_permalink((int) (get_page_by_path('indice-de-lecciones')?->ID ?? 0))
                ?: home_url('/indice-de-lecciones/'),
            'label' => __('Ver índice', 'sage'),
        ],
        'ENLACE_LECCION_INICIO' => [
            'href' => home_url('/lecciones-del-cde/urantia/que-es-el-libro-de-urantia/'),
            'label' => __('Ver lección', 'sage'),
        ],
        'ENLACE_TELEGRAM' => [
            'href' => 'https://t.me/+RJCRMR-axzzgR2Ej',
            'label' => __('Unirme a Telegram', 'sage'),
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ],
        'ENLACE_CUENTA' => [
            'href' => $accountUrl,
            'label' => __('Abrir tu cuenta', 'sage'),
        ],
    ];

    return apply_filters('sage/cde_membership_placeholder_links', $links);
}

/**
 * Replace editorial PMPro placeholders with runtime links for the current environment.
 */
function replace_cde_membership_placeholders(string $message): string
{
    $links = cde_membership_placeholder_links();

    foreach ($links as $token => $link) {
        $href = esc_url((string) ($link['href'] ?? ''));
        $label = esc_html((string) ($link['label'] ?? $href));

        if ($href === '') {
            continue;
        }

        $attributes = sprintf('href="%s"', $href);

        if (! empty($link['target'])) {
            $attributes .= sprintf(' target="%s"', esc_attr((string) $link['target']));
        }

        if (! empty($link['rel'])) {
            $attributes .= sprintf(' rel="%s"', esc_attr((string) $link['rel']));
        }

        $attributes .= ' style="color:#b50000;text-decoration:none;"';

        $anchor = sprintf('<a %s>%s</a>', $attributes, $label);
        $message = str_replace(sprintf('[%s]', $token), $anchor, $message);
    }

    return $message;
}

/**
 * Normalize editorial PMPro HTML for email clients.
 *
 * We keep the richer HTML for the web experience, but flatten list markup in emails
 * to avoid relying on inconsistent list rendering across email clients.
 */
function normalize_cde_membership_email_html(string $message): string
{
    if (trim($message) === '') {
        return $message;
    }

    $message = preg_replace_callback(
        '/<ul[^>]*>(.*?)<\/ul>/is',
        static function (array $matches): string {
            if (empty($matches[1]) || !is_string($matches[1])) {
                return '';
            }

            preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $matches[1], $items);
            $list_items = array_values(
                array_filter(
                    array_map(
                        static fn($item) => is_string($item) ? trim($item) : '',
                        $items[1] ?? []
                    ),
                    static fn(string $item): bool => $item !== ''
                )
            );

            if ($list_items === []) {
                return '';
            }

            $lines = array_map(
                static fn(string $item): string => '- ' . $item,
                $list_items
            );

            return '<p style="margin:0 0 24px;">' . implode('<br />', $lines) . '</p>';
        },
        $message
    );

    return is_string($message) ? $message : '';
}

/**
 * Render PMPro account level message with theme placeholder replacements.
 *
 * PMPro stores the message as editable HTML in level meta. We keep that editorial
 * workflow and only swap placeholder tokens for environment-aware links at render time.
 */
function render_cde_membership_account_level_message(object $level): void
{
    $message = get_pmpro_membership_level_meta((int) $level->id, 'membership_account_message', true);

    if (! is_string($message) || trim($message) === '') {
        return;
    }

    $message = replace_cde_membership_placeholders($message);
?>
    <div class="<?php echo esc_attr(pmpro_get_element_class('pmpro_account-membership-message')); ?>">
        <?php echo wpautop(wp_kses_post($message)); ?>
    </div>
<?php
}

/**
 * Replace CDE placeholders inside PMPro email template variables.
 */
function replace_cde_membership_placeholders_in_pmpro_email_data($data, $email)
{
    if (! is_array($data)) {
        return $data;
    }

    $message = $data['membership_level_confirmation_message'] ?? null;

    if (is_string($message) && trim($message) !== '') {
        $data['membership_level_confirmation_message'] = normalize_cde_membership_email_html(
            replace_cde_membership_placeholders($message)
        );
    }

    return $data;
}

/**
 * Replace CDE placeholders in the PMPro checkout confirmation page.
 */
function replace_cde_membership_placeholders_in_pmpro_confirmation_message(string $message): string
{
    if (trim($message) === '') {
        return $message;
    }

    return replace_cde_membership_placeholders($message);
}

/**
 * Load a PMPro email partial from the theme if it exists.
 */
function get_pmpro_email_partial_from_theme(string $template): ?string
{
    $path = get_stylesheet_directory() . '/paid-memberships-pro/email/' . $template . '.html';
    if (! file_exists($path)) {
        return null;
    }

    $contents = file_get_contents($path);

    return is_string($contents) && trim($contents) !== '' ? $contents : null;
}

/**
 * Use theme files for PMPro email header/footer, which otherwise fall back to
 * PMPro's built-in defaults before checking the theme directory.
 */
function override_pmpro_email_header_from_theme(string $header): string
{
    return get_pmpro_email_partial_from_theme('header') ?? $header;
}

/**
 * Use the theme footer partial for PMPro emails when present.
 */
function override_pmpro_email_footer_from_theme(string $footer): string
{
    return get_pmpro_email_partial_from_theme('footer') ?? $footer;
}

add_action('after_setup_theme', function () {
    if (! function_exists('pmpro_display_member_account_level_message')) {
        return;
    }

    remove_action(
        'pmpro_membership_account_after_level_card_content',
        'pmpro_display_member_account_level_message'
    );

    add_action(
        'pmpro_membership_account_after_level_card_content',
        __NAMESPACE__ . '\\render_cde_membership_account_level_message'
    );
}, 30);

add_filter(
    'pmpro_email_data',
    __NAMESPACE__ . '\\replace_cde_membership_placeholders_in_pmpro_email_data',
    20,
    2
);

add_filter(
    'pmpro_confirmation_message',
    __NAMESPACE__ . '\\replace_cde_membership_placeholders_in_pmpro_confirmation_message',
    20
);

add_filter(
    'pmpro_email_header',
    __NAMESPACE__ . '\\override_pmpro_email_header_from_theme',
    20
);

add_filter(
    'pmpro_email_footer',
    __NAMESPACE__ . '\\override_pmpro_email_footer_from_theme',
    20
);

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'sage'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'sage'),
        'id' => 'sidebar-footer',
    ] + $config);
});
