<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class Tocbot extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $builder = new FieldsBuilder('tabla_de_contenidos');

        $builder
            ->setLocation('post_type', '==', 'page');

        $builder
            ->addTrueFalse('has_toc', [
                'label' => 'Generar tabla de contenidos',
                'instructions' => 'Para que funcione hay que insertar un ID en cada bloque del tipo header: seleccionar el bloque y en "advanced > HTML anchor" introducir una palabra única" ',
                'message' => '',
                'default_value' => 0,
                'ui' => 1,
                'ui_on_text' => 'Sí',
                'ui_off_text' => 'No',
            ]);

        return $builder->build();
    }
}
