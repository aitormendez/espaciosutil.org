@props([
    'levels' => null, // optional prefiltered array of PMPro levels
])

@php
    /*
     |--------------------------------------------------------------------------
     | 1) Origen de datos
     |--------------------------------------------------------------------------
     | - $levels: si se pasa por props, se usa tal cual. Si no, se carga de PMPro.
     | - $pairedSeries (HARCODADO TEMPORALMENTE): lista de "series" donde cada
     |   elemento define los IDs de nivel para el plan mensual y el plan anual.
     |   Más adelante esto se moverá a ACF Options.
     |   Ejemplo para activar una tarjeta (descomenta y pon tus IDs):
     |   $pairedSeries = [
     |       [ 'monthly_id' => 3,  'yearly_id' => 4 ],   // Serie 1
     |       [ 'monthly_id' => 7,  'yearly_id' => 8 ],   // Serie 2
     |   ];
     */

    // Cargar niveles desde PMPro si no se pasan por props
    $all = $levels;
    if (is_null($all)) {
        $all = function_exists('pmpro_getAllLevels') ? pmpro_getAllLevels(true, true) : [];
    }

    // Indexar por ID para resolver rápido
    $byId = [];
    if (is_array($all)) {
        foreach ($all as $lvl) {
            if (is_object($lvl) && isset($lvl->id)) {
                $byId[(int) $lvl->id] = $lvl;
            }
        }
    }

    // --- DATASET DE PARES ---
    // Preferimos ACF (Opciones) si hay filas; si no, usamos el dataset manual (fallback)

    // Fallback manual por si ACF no tiene datos aún
    $pairedSeriesManual = [['monthly_id' => 2, 'yearly_id' => 3]];

    // Intentar leer desde ACF Options
    $pairedSeries = [];
    if (function_exists('get_field')) {
        $acfRows = get_field('series_membresia', 'option');
        if (is_array($acfRows) && count($acfRows) > 0) {
            // Ordenar por 'order' ascendente si existe
            usort($acfRows, function ($a, $b) {
                $oa = isset($a['order']) ? (int) $a['order'] : 0;
                $ob = isset($b['order']) ? (int) $b['order'] : 0;
                return $oa <=> $ob;
            });

            foreach ($acfRows as $row) {
                $imageUrl = isset($row['image']) ? $row['image'] : null;
                $imageSrcset = null;
                if (
                    $imageUrl &&
                    function_exists('attachment_url_to_postid') &&
                    function_exists('wp_get_attachment_image_srcset')
                ) {
                    $attachment_id = attachment_url_to_postid($imageUrl);
                    if ($attachment_id) {
                        $imageSrcset = wp_get_attachment_image_srcset($attachment_id, 'full');
                    }
                }

                $pairedSeries[] = [
                    'monthly_id' => isset($row['monthly_level_id']) ? (int) $row['monthly_level_id'] : 0,
                    'semiannual_id' => isset($row['semiannual_level_id']) ? (int) $row['semiannual_level_id'] : 0,
                    'yearly_id' => isset($row['yearly_level_id']) ? (int) $row['yearly_level_id'] : 0,
                    'display_name' => isset($row['display_name']) ? (string) $row['display_name'] : '',
                    'image' => $imageUrl,
                    'image_srcset' => $imageSrcset,
                    'image_sizes' => '(min-width: 640px) 20vw, 100vw',
                    'short_description' => isset($row['short_description']) ? (string) $row['short_description'] : '',
                    'featured' => !empty($row['featured']),
                ];
            }
        }
    }

    // Si ACF no trajo nada, usamos el dataset manual
    if (empty($pairedSeries)) {
        $pairedSeries = $pairedSeriesManual;
    }

    // Helpers locales
    $resolveLevel = function ($id) use ($byId) {
        if (!$id) {
            return null;
        }
        if (isset($byId[$id])) {
            return $byId[$id];
        }
        return function_exists('pmpro_getLevel') ? pmpro_getLevel((int) $id) : null;
    };

    $extractRecurringAmount = function ($level, $expectedPeriod) {
        if (!$level) {
            return null;
        }
        $amount =
            isset($level->billing_amount) && $level->billing_amount !== '' ? (float) $level->billing_amount : null;
        $has_cycle = !empty($level->cycle_number) && !empty($level->cycle_period);
        if ($amount === null || !$has_cycle) {
            return null;
        }
        $number = (int) $level->cycle_number;
        $period = strtolower((string) $level->cycle_period);

        // Normalizamos periodos
        $isMonths = in_array($period, ['month', 'months'], true);
        $isYears = in_array($period, ['year', 'years'], true);

        if ($expectedPeriod === 'month' && $isMonths && $number === 1) {
            return $amount;
        }
        if ($expectedPeriod === 'semi' && $isMonths && $number === 6) {
            return $amount;
        }
        if ($expectedPeriod === 'year' && $isYears && $number === 1) {
            return $amount;
        }
        return null;
    };

    $isSignupAllowed = function ($level) {
        if (!$level) {
            return false;
        }
        return isset($level->allow_signups) ? (bool) $level->allow_signups : true; // visible por defecto
    };

    // URL de cuenta de membresía
    $getAccountUrl = function () {
        if (function_exists('pmpro_url')) {
            return pmpro_url('account');
        }
        $base = null;
        if (function_exists('get_option')) {
            $page_id = (int) get_option('pmpro_account_page_id');
            if ($page_id && function_exists('get_permalink')) {
                $base = get_permalink($page_id);
            }
        }
        return $base ?: home_url('/membership-account/');
    };
@endphp

<div class="pricing-table mx-auto w-full px-4 py-8">
    <div class="flex flex-col">
        @if (!empty($pairedSeries))
            {{-- MODO: PARES DEFINIDOS (tarjeta por serie con mensual + anual) --}}
            @foreach ($pairedSeries as $pair)
                @php
                    $mid = isset($pair['monthly_id']) ? (int) $pair['monthly_id'] : 0;
                    $sid = isset($pair['semiannual_id']) ? (int) $pair['semiannual_id'] : 0;
                    $yid = isset($pair['yearly_id']) ? (int) $pair['yearly_id'] : 0;

                    $mLevel = $resolveLevel($mid);
                    $sLevel = $resolveLevel($sid);
                    $yLevel = $resolveLevel($yid);

                    // Si no hay ningún nivel válido, saltamos
                    $base = $mLevel ?: ($sLevel ?: $yLevel);
                    if (!$base) {
                        continue;
                    }

                    // Nombre/descripcion base con overrides desde ACF
                    $name = !empty($pair['display_name']) ? $pair['display_name'] : $base->name ?? '';
                    $description = !empty($pair['short_description'])
                        ? $pair['short_description']
                        : $base->description ?? '';
                    $image = $pair['image'] ?? null; // URL según ACF
                    $imageSrcset = $pair['image_srcset'] ?? null;
                    $imageSizes = $pair['image_sizes'] ?? '(min-width: 640px) 20vw, 100vw';

                    // Precios recurrentes
                    $priceMonthly = $extractRecurringAmount($mLevel, 'month');
                    $priceSemi = $extractRecurringAmount($sLevel, 'semi');
                    $priceYearly = $extractRecurringAmount($yLevel, 'year');

                    // Firmar si permiten registro
                    $allowMonthly = $isSignupAllowed($mLevel);
                    $allowSemi = $isSignupAllowed($sLevel);
                    $allowYearly = $isSignupAllowed($yLevel);

                    // Si ninguno de los tres planes permite altas, no mostramos la tarjeta
                    if (!$allowMonthly && !$allowSemi && !$allowYearly) {
                        continue;
                    }

                    // URLs y labels según estado del usuario (suscrito o no)
                    $monthlyLabel = 'Suscríbete al plan mensual';
                    $semiLabel = 'Suscríbete al plan semestral';
                    $yearlyLabel = 'Suscríbete al plan anual';

                    // Mensual
                    if ($mLevel && $allowMonthly && $priceMonthly !== null) {
                        $isMemberMonthly = function_exists('pmpro_hasMembershipLevel')
                            ? pmpro_hasMembershipLevel((int) $mLevel->id)
                            : false;
                        $monthlySubscribed = $isMemberMonthly;
                        if ($isMemberMonthly) {
                            $checkoutMonthlyUrl = $getAccountUrl();
                            $monthlyLabel = 'Suscrito';
                        } else {
                            $checkoutMonthlyUrl = home_url('/pago-de-membresia/?pmpro_level=' . (int) $mLevel->id);
                        }
                    } else {
                        $checkoutMonthlyUrl = null;
                        $monthlySubscribed = false;
                    }

                    // Semestral
                    if ($sLevel && $allowSemi && $priceSemi !== null) {
                        $isMemberSemi = function_exists('pmpro_hasMembershipLevel')
                            ? pmpro_hasMembershipLevel((int) $sLevel->id)
                            : false;
                        $semiSubscribed = $isMemberSemi;
                        if ($isMemberSemi) {
                            $checkoutSemiUrl = $getAccountUrl();
                            $semiLabel = 'Suscrito';
                        } else {
                            $checkoutSemiUrl = home_url('/pago-de-membresia/?pmpro_level=' . (int) $sLevel->id);
                        }
                    } else {
                        $checkoutSemiUrl = null;
                        $semiSubscribed = false;
                    }

                    // Anual
                    if ($yLevel && $allowYearly && $priceYearly !== null) {
                        $isMemberYearly = function_exists('pmpro_hasMembershipLevel')
                            ? pmpro_hasMembershipLevel((int) $yLevel->id)
                            : false;
                        $yearlySubscribed = $isMemberYearly;
                        if ($isMemberYearly) {
                            $checkoutYearlyUrl = $getAccountUrl();
                            $yearlyLabel = 'Suscrito';
                        } else {
                            $checkoutYearlyUrl = home_url('/pago-de-membresia/?pmpro_level=' . (int) $yLevel->id);
                        }
                    } else {
                        $checkoutYearlyUrl = null;
                        $yearlySubscribed = false;
                    }
                @endphp

                <x-pricing-package :id="$base->id" :name="$name" :description="$description" :image="$image"
                    :image-srcset="$imageSrcset" :image-sizes="$imageSizes" :price-monthly="$priceMonthly" :price-semi="$priceSemi" :price-yearly="$priceYearly"
                    :checkout-monthly-url="$checkoutMonthlyUrl" :checkout-semi-url="$checkoutSemiUrl" :checkout-yearly-url="$checkoutYearlyUrl" :monthly-label="$monthlyLabel" :semi-label="$semiLabel"
                    :yearly-label="$yearlyLabel" :monthly-subscribed="$monthlySubscribed" :semi-subscribed="$semiSubscribed" :yearly-subscribed="$yearlySubscribed" />
            @endforeach
        @else
            {{-- MODO FALLBACK: sin pares definidos, listamos niveles sueltos como antes --}}
            @foreach ($all as $level)
                @php
                    $allow = isset($level->allow_signups) ? (int) $level->allow_signups : 1; // visible por defecto
                    if (!$allow) {
                        continue;
                    }

                    $name = $level->name ?? '';
                    $description = $level->description ?? '';
                    $image = null; // sin imagen por defecto

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

                    // URLs y labels en modo fallback
                    $monthly_label = 'Plan mensual';
                    $yearly_label = 'Plan anual';

                    // Mensual
                    if ($monthly) {
                        $isMemberMonthly = function_exists('pmpro_hasMembershipLevel')
                            ? pmpro_hasMembershipLevel((int) $level->id)
                            : false;
                        $monthly_subscribed = $isMemberMonthly;
                        if ($isMemberMonthly) {
                            $checkout_monthly = $getAccountUrl();
                            $monthly_label = 'Suscrito';
                        } else {
                            $checkout_monthly = home_url('/pago-de-membresia/?pmpro_level=' . (int) $level->id);
                        }
                    } else {
                        $checkout_monthly = null;
                        $monthly_subscribed = false;
                    }

                    // Anual
                    if ($yearly) {
                        $isMemberYearly = function_exists('pmpro_hasMembershipLevel')
                            ? pmpro_hasMembershipLevel((int) $level->id)
                            : false;
                        $yearly_subscribed = $isMemberYearly;
                        if ($isMemberYearly) {
                            $checkout_yearly = $getAccountUrl();
                            $yearly_label = 'Suscrito';
                        } else {
                            $checkout_yearly = home_url('/pago-de-membresia/?pmpro_level=' . (int) $level->id);
                        }
                    } else {
                        $checkout_yearly = null;
                        $yearly_subscribed = false;
                    }
                @endphp

                <x-pricing-package :id="$level->id" :name="$name" :description="$description" :image="$image"
                    :price-monthly="$monthly" :price-yearly="$yearly" :checkout-monthly-url="$checkout_monthly" :checkout-yearly-url="$checkout_yearly" :monthly-label="$monthly_label"
                    :yearly-label="$yearly_label" :monthly-subscribed="$monthly_subscribed" :yearly-subscribed="$yearly_subscribed" />
            @endforeach
        @endif

    </div>
</div>
