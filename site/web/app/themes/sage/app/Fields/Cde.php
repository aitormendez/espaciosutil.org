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
        $cde = new FieldsBuilder('cde_fields', [
            'title' => 'Lección CDE',
            'position' => 'acf_after_title',
            'style' => 'seamless',
            'show_in_rest' => 1,
        ]);

        $cde->setLocation('post_type', '==', 'cde');

        $cde
            ->addTab('general', [
                'label' => 'General',
                'placement' => 'top',
            ])
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

            ->addTab('lesson_subindex', [
                'label' => 'Subíndice',
                'placement' => 'top',
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
            ->endRepeater()
            ->addTextarea('lesson_subindex_import', [
                'label' => 'Importar subíndice desde JSON',
                'instructions' => 'Pega un JSON con objetos {title, level, timecode?, anchor?}. Se procesará al guardar la lección.',
                'required' => 0,
                'wrapper' => [
                    'width' => '',
                ],
                'rows' => 6,
                'new_lines' => '',
            ])

            ->addTab('lesson_media', [
                'label' => 'Medios (Video/Audio)',
                'placement' => 'top',
            ])
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
                'instructions' => 'Nombre descriptivo. Dejar en blanco para usar el título de la lección.',
                'required' => 0,
                'wrapper' => [
                    'width' => '100',
                ],
            ])
            ->addText('featured_audio_id', [
                'label' => 'ID del Audio (Bunny.net Stream ID)',
                'instructions' => 'Introduce el Stream ID del audio en Bunny.net (usa la misma Video Library).',
                'required' => 0,
                'wrapper' => [
                    'width' => '50',
                ],
            ])
            ->addText('featured_audio_library_id', [
                'label' => 'ID de la Librería para Audio (Bunny.net Video Library ID)',
                'instructions' => 'Deja el valor por defecto si el audio vive en la misma librería que los videos.',
                'default_value' => '457097',
                'required' => 0,
                'wrapper' => [
                    'width' => '50',
                ],
            ])
            ->addText('featured_audio_name', [
                'label' => 'Nombre del Audio',
                'instructions' => 'Nombre descriptivo. Dejar en blanco para usar el título de la lección.',
                'required' => 0,
                'wrapper' => [
                    'width' => '100',
                ],
            ])

            ->addTab('quiz', [
                'label' => 'Cuestionario',
                'placement' => 'top',
            ])
            ->addTrueFalse('quiz_enabled', [
                'label' => 'Activar cuestionario',
                'instructions' => 'Muestra el cuestionario de la lección en el frontend. Desactívalo para ocultarlo.',
                'default_value' => 0,
                'ui' => 1,
                'ui_on_text' => 'Activo',
                'ui_off_text' => 'Oculto',
            ])
            ->addTextarea('quiz_json_import', [
                'label' => 'Importar cuestionario desde JSON',
                'instructions' => 'Pega un JSON con preguntas y respuestas para rellenar el cuestionario al guardar. Formato: [{"question": "...", "answers": [{"text": "...", "is_correct": true}]}].',
                'required' => 0,
                'rows' => 6,
                'new_lines' => '',
            ])
            ->addRepeater('quiz_questions', [
                'label' => 'Preguntas',
                'instructions' => 'Añade una pregunta y sus posibles respuestas. Marca todas las respuestas correctas que apliquen.',
                'required' => 0,
                'min' => 0,
                'layout' => 'block',
                'button_label' => 'Añadir pregunta',
            ])
            ->addText('question', [
                'label' => 'Pregunta',
                'instructions' => 'Texto de la pregunta.',
                'required' => 1,
            ])
            ->addRepeater('answers', [
                'label' => 'Respuestas',
                'instructions' => 'Incluye todas las opciones. Puede haber múltiples respuestas correctas.',
                'required' => 1,
                'min' => 2,
                'layout' => 'column',
                'button_label' => 'Añadir respuesta',
            ])
            ->addText('answer_text', [
                'label' => 'Respuesta',
                'required' => 1,
                'wrapper' => [
                    'width' => '70',
                ],
            ])
            ->addTrueFalse('is_correct', [
                'label' => 'Correcta',
                'instructions' => 'Marca si esta respuesta es correcta.',
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => [
                    'width' => '30',
                ],
            ])
            ->endRepeater()
            ->endRepeater()
            ->addTab('related_lessons', [
                'label' => 'Lecciones relacionadas',
                'placement' => 'top',
            ])
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

        return $cde->build();
    }
}
