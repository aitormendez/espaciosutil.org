<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class RelatedLessons extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $relatedLessons = new FieldsBuilder('related_lessons', [
            'title' => 'Lecciones Relacionadas',
            'style' => 'default',
            'position' => 'normal',
            'show_in_rest' => 1,
        ]);

        $relatedLessons
            ->setLocation('post_type', '==', 'cde');

        $relatedLessons
            ->addRelationship('cde_related_lessons', [
                'label' => 'Lecciones Relacionadas',
                'instructions' => 'Selecciona otras lecciones para mostrarlas como contenido relacionado.',
                'required' => 0,
                'post_type' => ['cde'],
                'taxonomy' => [],
                'filters' => [
                    0 => 'search',
                    1 => 'post_type',
                ],
                'elements' => '',
                'min' => '',
                'max' => '',
                'return_format' => 'object',
            ]);

        return $relatedLessons->build();
    }
}
