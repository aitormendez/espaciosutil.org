<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class MenuItems extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $menu_items = new FieldsBuilder('menu_items');

        $menu_items
            ->setLocation('nav_menu_item', '==', 'all');

        $menu_items
            ->addColorPicker('menu_item_bg_color', [
                'label' => 'Color de fondo para constelaciones',
                'default_value' => '#000000',
            ]);

        return $menu_items->build();
    }
}