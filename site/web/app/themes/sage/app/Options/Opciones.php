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

        // PMPro native level groups are now the source of truth for memberships.

        return $fields->build();
    }
}
