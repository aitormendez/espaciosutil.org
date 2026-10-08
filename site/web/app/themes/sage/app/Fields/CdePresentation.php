<?php

namespace App\Fields;

use EspacioSutil\Presentations\Service;
use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class CdePresentation extends Field
{
    public function fields(): array
    {
        if (! class_exists(Service::class)) {
            return [];
        }
        $fields = new FieldsBuilder('cde_presentation', ['title' => 'Presentación', 'show_in_rest' => 0]);
        $fields->setLocation('post_type', '==', 'cde');
        $fields->addTrueFalse('lesson_presentation_enabled', ['label' => 'Mostrar presentación', 'ui' => 1, 'default_value' => 0]);
        $fields->addField('presentation_help', 'message', [
            'label' => 'Diapositivas importadas',
            'message' => 'La presentación se prepara desde el PDF y se importa con la herramienta del curso. Activar esta opción requiere un paquete importado.',
        ]);

        return $fields->build();
    }
}
