<?php
// This file is generated. Do not modify it manually.
return array(
	'video' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'espacio-sutil-blocks/video',
		'version' => '0.1.0',
		'title' => 'Bunny.net Video',
		'category' => 'espacio-sutil',
		'icon' => 'video-alt3',
		'description' => 'Bloque de video personalizado con integración Bunny.net para Espacio Sutil.',
		'textdomain' => 'espacio-sutil-blocks',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php',
		'attributes' => array(
			'libraryId' => array(
				'type' => 'string',
				'default' => '457097'
			),
			'videoId' => array(
				'type' => 'string',
				'default' => ''
			),
			'videoSrc' => array(
				'type' => 'string',
				'default' => ''
			),
			'thumbnailUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'align' => array(
				'type' => 'string',
				'default' => 'none'
			),
			'autoplay' => array(
				'type' => 'boolean',
				'default' => false
			),
			'loop' => array(
				'type' => 'boolean',
				'default' => false
			),
			'muted' => array(
				'type' => 'boolean',
				'default' => false
			),
			'controls' => array(
				'type' => 'boolean',
				'default' => true
			),
			'playsInline' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'spacing' => array(
				'margin' => false,
				'padding' => false
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true,
					'style' => true,
					'width' => true
				)
			),
			'html' => false
		)
	)
);
