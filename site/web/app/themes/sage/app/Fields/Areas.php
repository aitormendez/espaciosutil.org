<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class Areas extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $builder = new FieldsBuilder('datos_del_area');

        $builder
            ->setLocation('post_type', '==', 'area');

        $builder
            ->addRelationship('area_series_relacionadas', [
                'label' => 'Series relacionadas',
                'instructions' => 'Las series que se añadan aquí aparecerán dentro de este área',
                'required' => 0,
                'post_type' => ['serie'],
                'taxonomy' => [],
                'filters' => [
                    0 => 'search',
                    2 => 'taxonomy',
                ],
                'elements' => '',
                'min' => '',
                'max' => '',
                'return_format' => 'object',
            ]);
        return $builder->build();
    }
}
