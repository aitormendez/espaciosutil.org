<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class Cde extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields(): array
    {
        $cde = new FieldsBuilder('cde_fields');

        $cde
            ->setLocation('post_type', '==', 'cde');

        $cde
            ->addTrueFalse('active_lesson', [
                'label' => 'Lección activa',
                'instructions' => 'Desactívalo para ocultar el enlace en el índice y marcar la lección como pendiente.',
                'default_value' => 1,
                'ui' => 1,
                'ui_on_text' => 'Activa',
                'ui_off_text' => 'Inactiva',
            ])
            ->addWysiwyg('rich_excerpt', [
                'label' => 'Extracto Enriquecido',
                'instructions' => 'Contenido del extracto visible para todos.',
                'required' => 0,
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0,
            ]);

        return $cde->build();
    }
}
