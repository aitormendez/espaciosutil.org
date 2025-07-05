<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class Series extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $builder = new FieldsBuilder('datos_de_la_serie');

        $builder
            ->setLocation('post_type', '==', 'serie');

        $builder
            ->addRepeater('serie_enlaces', [
                'label' => 'Enlaces',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => [],
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'min' => 0,
                'max' => 0,
                'layout' => 'table',
                'button_label' => 'Añadir enlace',
                'sub_fields' => [],
            ])
            ->addRadio('serie_formato', [
                'label' => 'Formato',
                'instructions' => '',
                'required' => 0,
                'choices' => [
                    'video' => 'Video',
                    'audio' => 'Audio',
                ],
                'allow_null' => 0,
                'other_choice' => 0,
                'save_other_choice' => 0,
                'default_value' => '',
                'layout' => 'horizontal',
                'return_format' => 'value',
            ])
            ->addUrl('serie_enlace', [
                'label' => 'Enlace a la serie',
                'instructions' => 'La URL donde se aloja la serie',
                'required' => 0,
                'return_format' => 'array',
            ])
            ->endRepeater();

        return $builder->build();
    }
}
