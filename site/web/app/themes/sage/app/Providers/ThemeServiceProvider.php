<?php

namespace App\Providers;

use Roots\Acorn\Sage\SageServiceProvider;

class ThemeServiceProvider extends SageServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Asignar rol "estudiante" si el pedido completado incluye un curso
        add_action('woocommerce_order_status_completed', [$this, 'asignarRolEstudiantePorCategoria']);
    }

    public function asignarRolEstudiantePorCategoria($order_id)
    {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        if (!$user_id) {
            return;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $product = wc_get_product($item->get_product_id());
            if (!$product) {
                continue;
            }

            // Verifica si el producto pertenece a la categoría "cursos"
            if (has_term('curso', 'product_cat', $product->get_id())) {
                if (!in_array('estudiante', (array) $user->roles)) {
                    $user->add_role('estudiante');
                }
                break;
            }
        }
    }
}
