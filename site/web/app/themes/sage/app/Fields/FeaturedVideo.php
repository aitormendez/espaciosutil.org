<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class FeaturedVideo extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields(): array
    {
        $featuredVideo = new FieldsBuilder('featured_video');

        $featuredVideo
            ->setLocation('post_type', '==', 'cde');

        $featuredVideo
            ->addText('featured_video_id', [
                'label' => 'ID del Video (Bunny.net Stream ID)',
                'instructions' => 'Introduce el Stream ID del video de Bunny.net',
                'required' => 0,
                'wrapper' => [
                    'width' => '50',
                ],
            ])
            ->addText('featured_video_library_id', [
                'label' => 'ID de la Librería (Bunny.net Video Library ID)',
                'instructions' => 'Introduce el ID de la librería de video de Bunny.net',
                'default_value' => '457097',
                'required' => 0,
                'wrapper' => [
                    'width' => '50',
                ],
            ])
            ->addText('featured_video_name', [
                'label' => 'Nombre del Video',
                'instructions' => 'Nombre descriptivo. Dejar en blanco para usar el título de la lección.',
                'required' => 0,
                'wrapper' => [
                    'width' => '100',
                ],
            ])
            ->addText('featured_audio_id', [
                'label' => 'ID del Audio (Bunny.net Stream ID)',
                'instructions' => 'Introduce el Stream ID del audio en Bunny.net (usa la misma Video Library).',
                'required' => 0,
                'wrapper' => [
                    'width' => '50',
                ],
            ])
            ->addText('featured_audio_library_id', [
                'label' => 'ID de la Librería para Audio (Bunny.net Video Library ID)',
                'instructions' => 'Deja el valor por defecto si el audio vive en la misma librería que los videos.',
                'default_value' => '457097',
                'required' => 0,
                'wrapper' => [
                    'width' => '50',
                ],
            ])
            ->addText('featured_audio_name', [
                'label' => 'Nombre del Audio',
                'instructions' => 'Nombre descriptivo. Dejar en blanco para usar el título de la lección.',
                'required' => 0,
                'wrapper' => [
                    'width' => '100',
                ],
            ]);

        return $featuredVideo->build();
    }
}
