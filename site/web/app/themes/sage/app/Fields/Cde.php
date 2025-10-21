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
            ])
            ->addRepeater('lesson_subindex_items', [
                'label' => 'Subíndice de la lección',
                'instructions' => 'Define los apartados tratados en la lección. Usa el campo Nivel para indicar la jerarquía (1 = inmediato, 2 = subapartado, etc.).',
                'required' => 0,
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'min' => 0,
                'layout' => 'block',
                'button_label' => 'Añadir apartado',
            ])
                ->addSelect('level', [
                    'label' => 'Nivel',
                    'instructions' => 'Determina la profundidad dentro del subíndice (1 a 4).',
                    'choices' => [
                        1 => 'Nivel 1',
                        2 => 'Nivel 2',
                        3 => 'Nivel 3',
                        4 => 'Nivel 4',
                    ],
                    'default_value' => 1,
                    'required' => 1,
                    'return_format' => 'value',
                ])
                ->addText('title', [
                    'label' => 'Título',
                    'instructions' => 'Nombre del apartado tal como aparece en el subíndice.',
                    'required' => 1,
                ])
                ->addText('timecode', [
                    'label' => 'Marca de tiempo',
                    'instructions' => 'Formato hh:mm:ss para saltar al momento del video (opcional).',
                    'required' => 0,
                    'placeholder' => '00:05:30',
                    'wrapper' => [
                        'width' => '50',
                    ],
                ])
                ->addText('anchor', [
                    'label' => 'Ancla',
                    'instructions' => 'Slug opcional para enlazar secciones del contenido escrito.',
                    'required' => 0,
                    'wrapper' => [
                        'width' => '50',
                    ],
                ])
            ->endRepeater();

        $cde->addTextarea('lesson_subindex_import', [
            'label' => 'Importar subíndice desde JSON',
            'instructions' => 'Pega un JSON con objetos {title, level, timecode?, anchor?}. Se procesará al guardar la lección.',
            'required' => 0,
            'wrapper' => [
                'width' => '',
            ],
            'rows' => 6,
            'new_lines' => '',
        ]);

        return $cde->build();
    }
}
