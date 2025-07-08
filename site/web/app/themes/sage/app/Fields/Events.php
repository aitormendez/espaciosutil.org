<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class Events extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $builder = new FieldsBuilder('datos_del_evento');

        $builder
            ->setLocation('post_type', '==', 'event');

        $builder
            ->addDateTimePicker('event_start_date', [
                'label' => 'Inicio del evento',
                'return_format' => 'Y-m-d H:i:s',
                'required' => 1,
            ])
            ->addDateTimePicker('event_end_date', [
                'label' => 'Fin del evento',
                'return_format' => 'Y-m-d H:i:s',
                'required' => 1,
            ]);

        return $builder->build();
    }
}
