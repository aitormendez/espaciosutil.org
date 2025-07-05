<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class Reveladores extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $builder = new FieldsBuilder('datos_del_revelador');

        $builder
            ->setLocation('taxonomy', '==', 'revelador')
            ->or('taxonomy', '==', 'facilitador')
            ->or('taxonomy', '==', 'canal');

        $builder
            ->addWysiwyg('revelador_texto', [
                'label' => 'Descripción',
                'instructions' => '',
                'required' => 0,
                'default_value' => '',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0,
            ]);

        return $builder->build();
    }
}
