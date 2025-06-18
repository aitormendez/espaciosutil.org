<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Post Types
    |--------------------------------------------------------------------------
    */

    'post' => [
        'noticia' => [
            'enter_title_here' => 'Título de la noticia',
            'menu_icon' => 'dashicons-megaphone',
            'supports' => ['title', 'editor', 'author', 'revisions', 'thumbnail', 'excerpt'],
            'show_in_rest' => true,
            'has_archive' => true,
        ],

        'event' => [
            'enter_title_here' => 'Nombre del evento',
            'menu_icon' => 'dashicons-calendar',
            'supports' => ['title', 'editor', 'author', 'revisions', 'thumbnail'],
            'show_in_rest' => true,
            'has_archive' => true,
            'labels' => [
                'singular' => 'Evento',
                'plural' => 'Eventos',
            ],
        ],

        'serie' => [
            'enter_title_here' => 'Nombre de la serie en YouTube',
            'menu_icon' => 'dashicons-youtube',
            'supports' => ['title', 'editor', 'author', 'revisions', 'thumbnail', 'excerpt'],
            'show_in_rest' => true,
            'has_archive' => true,
            'labels' => [
                'singular' => 'Serie',
                'plural' => 'Series',
            ],
            'admin_cols' => [
                'planeta' => [
                    'taxonomy' => 'planeta',
                ],
            ],
        ],

        'area' => [
            'enter_title_here' => 'Nombre del área',
            'menu_icon' => 'dashicons-grid-view',
            'supports' => ['title', 'editor', 'author', 'revisions', 'thumbnail', 'excerpt'],
            'show_in_rest' => true,
            'has_archive' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Taxonomies
    |--------------------------------------------------------------------------
    */

    'taxonomy' => [
        'revelador' => [
            'links' => ['serie'],
            'meta_box' => 'simple',
            'show_in_rest' => true,
            'labels' => [
                'singular' => 'revelador',
                'plural' => 'reveladores',
            ],
        ],

        'seccion' => [
            'links' => ['serie', 'area'],
            'meta_box' => 'simple',
            'labels' => [
                'singular' => 'sección',
                'plural' => 'secciones',
            ],
        ],

        'canal' => [
            'links' => ['serie'],
            'meta_box' => 'simple',
            'show_in_rest' => true,
            'labels' => [
                'singular' => 'Canal',
                'plural' => 'canales',
            ],
        ],

        'facilitador' => [
            'links' => ['serie'],
            'meta_box' => 'simple',
            'show_in_rest' => true,
            'labels' => [
                'singular' => 'facilitador',
                'plural' => 'facilitadores',
            ],
        ],

        'autor' => [
            'links' => ['serie'],
            'meta_box' => 'simple',
            'show_in_rest' => true,
            'labels' => [
                'singular' => 'autor',
                'plural' => 'autores',
            ],
        ],

        'planeta' => [
            'links' => ['page'],
            'meta_box' => 'simple',
            'hierarchical' => true,
            'exclusive' => true,
            'show_in_rest' => true,
        ],
    ],
];
