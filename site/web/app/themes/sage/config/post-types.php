<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Post Types
    |--------------------------------------------------------------------------
    |
    | Post types to be registered with Extended CPTs
    | <https://github.com/johnbillion/extended-cpts>
    |
    */

    'post_types' => [
        'noticia' => [
            'enter_title_here' => 'Título de la noticia',
            'menu_icon' => 'dashicons-megaphone',
            'supports' => ['title', 'editor', 'author', 'revisions', 'thumbnail', 'excerpt'],
            'show_in_rest' => true,
            'has_archive' => true,
            'names' => [
                'singular' => 'noticia',
                'plural' => 'noticias',
            ],
        ],

        'event' => [
            'enter_title_here' => 'Nombre del evento',
            'menu_icon' => 'dashicons-calendar',
            'supports' => ['title', 'editor', 'author', 'revisions', 'thumbnail'],
            'show_in_rest' => true,
            'has_archive' => true,
            'names' => [
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
            'names' => [
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
            'names' => [
                'singular' => 'area',
                'plural' => 'areas',
            ],
        ],

        'cde' => [
            'enter_title_here' => 'Título del contenido del curso',
            'menu_icon' => 'dashicons-book-alt',
            'supports' => ['title', 'editor', 'thumbnail', 'revisions', 'excerpt', 'page-attributes', 'comments'],
            'show_in_rest' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'cde'],
            'names' => [
                'singular' => 'Lección del CDE',
                'plural' => 'Lecciones del CDE',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Taxonomies
    |--------------------------------------------------------------------------
    |
    | Taxonomies to be registered with Extended CPTs library
    | <https://github.com/johnbillion/extended-cpts>
    |
    */

    'taxonomies' => [
        'revelador' => [
            'post_types' => ['serie', 'cde'],
            'meta_box' => 'simple',
            'show_in_rest' => true,
            'names' => [
                'singular' => 'revelador',
                'plural' => 'reveladores',
            ],
        ],

        'seccion' => [
            'post_types' => ['serie', 'area'],
            'meta_box' => 'simple',
            'names' => [
                'singular' => 'sección',
                'plural' => 'secciones',
            ],
        ],

        'canal' => [
            'post_types' => ['serie'],
            'meta_box' => 'simple',
            'show_in_rest' => true,
            'names' => [
                'singular' => 'Canal',
                'plural' => 'canales',
            ],
        ],

        'facilitador' => [
            'post_types' => ['serie'],
            'meta_box' => 'simple',
            'show_in_rest' => true,
            'names' => [
                'singular' => 'facilitador',
                'plural' => 'facilitadores',
            ],
        ],

        'autor' => [
            'post_types' => ['serie'],
            'meta_box' => 'simple',
            'show_in_rest' => true,
            'names' => [
                'singular' => 'autor',
                'plural' => 'autores',
            ],
        ],

        'planeta' => [
            'post_types' => ['page'],
            'meta_box' => 'simple',
            'hierarchical' => true,
            'exclusive' => true,
            'show_in_rest' => true,
            'names' => [
                'singular' => 'noticia',
                'plural' => 'noticias',
            ],
        ],

        'nivel_cde' => [
            'post_types' => ['cde'],
            'meta_box' => 'simple',
            'hierarchical' => true,
            'show_in_rest' => true,
            'names' => [
                'singular' => 'Nivel del curso',
                'plural' => 'Niveles del curso',
            ],
        ],
    ],
];
