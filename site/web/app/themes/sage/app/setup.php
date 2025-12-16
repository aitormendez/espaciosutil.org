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

    return '';
}, 10, 3);

add_filter('hf_form_response', function ($response, $form, $data) {
    if (($form->slug ?? '') !== 'contacto') {
        return $response;
    }

    if (isset($response['message']['type']) && $response['message']['type'] === 'error') {
        $response['message']['text'] = __('No pudimos verificar que eres humano. Inténtalo de nuevo.', 'sage');
    }

    return $response;
}, 10, 3);

// HTML Forms: permitir campos extra (p. ej. antispam) sin marcar como spam por tamaño del POST.
add_filter('hf_validate_form_request_size', '__return_false');

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
