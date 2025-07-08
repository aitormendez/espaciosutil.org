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
                'instructions' => 'Introduce el nombre del video que se mostrará en el reproductor.',
                'required' => 0,
                'wrapper' => [
                    'width' => '100',
                ],
            ]);

        return $featuredVideo->build();
    }
}
