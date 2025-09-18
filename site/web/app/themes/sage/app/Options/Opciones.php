<?php

namespace App\Options;

use Log1x\AcfComposer\Builder;
use Log1x\AcfComposer\Options as Field;

class Opciones extends Field
{
    /**
     * The option page menu name.
     *
     * @var string
     */
    public $name = 'Opciones';

    /**
     * The option page document title.
     *
     * @var string
     */
    public $title = 'Opciones | Options';

    /**
     * The option page field group.
     */
    public function fields(): array
    {
        $fields = Builder::make('opciones');

        // Repeater principal: definición de series con sus niveles
        $fields
            ->addRepeater('series_membresia', [
                'label' => 'Series de membresía',
                'instructions' => 'Empareja un nivel mensual, opcionalmente uno semestral, y uno anual por cada serie; añade los metadatos de la tarjeta.',
                'min' => 0,
            ])
            ->addText('display_name', [
                'label' => 'Nombre público de la tarjeta',
                'instructions' => 'Ej.: Ramtha, Curso de Desarrollo Espiritual, etc.',
                'required' => 0,
            ])
            ->addNumber('monthly_level_id', [
                'label' => 'ID nivel mensual (PMP)',
                'instructions' => 'Introduce el ID del nivel mensual en Paid Memberships Pro.',
                'min' => 1,
                'required' => 1,
            ])
            ->addNumber('semiannual_level_id', [
                'label' => 'ID nivel semestral (PMP)',
                'instructions' => 'Opcional. ID del nivel semestral en Paid Memberships Pro (ciclo de 6 meses).',
                'min' => 1,
                'required' => 0,
            ])
            ->addNumber('yearly_level_id', [
                'label' => 'ID nivel anual (PMP)',
                'instructions' => 'Introduce el ID del nivel anual en Paid Memberships Pro.',
                'min' => 1,
                'required' => 1,
            ])
            ->addImage('image', [
                'label' => 'Imagen de la tarjeta',
                'return_format' => 'url', // facilita el uso directo en el componente
                'preview_size' => 'medium',
            ])
            ->addTextarea('short_description', [
                'label' => 'Descripción corta',
                'instructions' => 'Opcional. Si se deja vacío, se usará la descripción del nivel.',
                'rows' => 3,
            ])
            ->addNumber('order', [
                'label' => 'Orden',
                'instructions' => 'Número para ordenar las tarjetas (ascendente).',
                'default_value' => 0,
            ])
            ->endRepeater();

        return $fields->build();
    }
}
