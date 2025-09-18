@props([
    'levels' => null, // optional prefiltered array of PMPro levels
])

@php
    // Cargar niveles desde PMPro si no se pasan por props
    $all = $levels;
    if (is_null($all)) {
        $all = function_exists('pmpro_getAllLevels') ? pmpro_getAllLevels(true, true) : [];
    }
@endphp

<div class="pricing-table mx-auto max-w-6xl px-4 py-8">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">

        @foreach ($all as $level)
            @php
                // Mostrar solo niveles "activos" (permiten nuevos registros)
                $allow = isset($level->allow_signups) ? (int) $level->allow_signups : 1; // visible por defecto
                if (!$allow) {
                    continue;
                }

                $name = $level->name ?? '';
                $description = $level->description ?? '';
                $image = null; // sin imagen por defecto (se puede añadir más tarde)

                // Determinar precio según el periodo del nivel
                $monthly = null;
                $yearly = null;
                $amount =
                    isset($level->billing_amount) && $level->billing_amount !== ''
                        ? (float) $level->billing_amount
                        : 0.0;
                $has_cycle = !empty($level->cycle_number) && !empty($level->cycle_period);
                if ($amount > 0 && $has_cycle) {
                    $period = strtolower((string) $level->cycle_period);
                    if ($period === 'month' || $period === 'months') {
                        $monthly = $amount;
                    } elseif ($period === 'year' || $period === 'years') {
                        $yearly = $amount;
                    }
                }

                // URL de checkout para este nivel concreto
                $checkout = home_url('/membership-checkout/?level=' . $level->id);
                $checkout_monthly = $monthly ? $checkout : null;
                $checkout_yearly = $yearly ? $checkout : null;
            @endphp

            <x-pricing-package :id="$level->id" :name="$name" :description="$description" :image="$image" :price-monthly="$monthly"
                :price-yearly="$yearly" :checkout-monthly-url="$checkout_monthly" :checkout-yearly-url="$checkout_yearly" />
        @endforeach

    </div>
</div>
