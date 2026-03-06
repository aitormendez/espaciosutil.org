@props([
    'levels' => null, // optional prefiltered array of PMPro levels
])

@php
    // Load levels from PMPro unless prefiltered levels are passed as props.
    $all = $levels;
    if (is_null($all)) {
        $all = function_exists('pmpro_getAllLevels') ? pmpro_getAllLevels(true, true) : [];
    }

    if (is_array($all) && function_exists('pmpro_sort_levels_by_order')) {
        $all = pmpro_sort_levels_by_order($all);
    }

    // Fast lookup by level ID.
    $byId = [];
    if (is_array($all)) {
        foreach ($all as $lvl) {
            if (is_object($lvl) && isset($lvl->id)) {
                $byId[(int) $lvl->id] = $lvl;
            }
        }
    }

    // Use PMPro native groups as source of truth.
    $levelGroups = function_exists('pmpro_get_level_groups_in_order') ? pmpro_get_level_groups_in_order() : [];
    if (!is_array($levelGroups)) {
        $levelGroups = [];
    }

    $resolveLevel = function ($id) use ($byId) {
        if (!$id) {
            return null;
        }

        if (isset($byId[$id])) {
            return $byId[$id];
        }

        return function_exists('pmpro_getLevel') ? pmpro_getLevel((int) $id) : null;
    };

    $resolveLevelsForGroup = function ($groupId) use ($all, $resolveLevel) {
        if (!function_exists('pmpro_get_level_ids_for_group')) {
            return [];
        }

        $groupLevelIds = pmpro_get_level_ids_for_group((int) $groupId);
        if (!is_array($groupLevelIds) || empty($groupLevelIds)) {
            return [];
        }

        $groupLevelIds = array_map('intval', $groupLevelIds);

        // Keep PMPro admin order when possible.
        $sortedLevels = [];
        if (is_array($all)) {
            foreach ($all as $levelCandidate) {
                if (
                    is_object($levelCandidate) &&
                    isset($levelCandidate->id) &&
                    in_array((int) $levelCandidate->id, $groupLevelIds, true)
                ) {
                    $sortedLevels[] = $levelCandidate;
                }
            }
        }

        if (!empty($sortedLevels)) {
            return $sortedLevels;
        }

        // Fallback if ordering source is not available.
        $resolvedLevels = [];
        foreach ($groupLevelIds as $groupLevelId) {
            $resolvedLevel = $resolveLevel($groupLevelId);
            if ($resolvedLevel) {
                $resolvedLevels[] = $resolvedLevel;
            }
        }

        return $resolvedLevels;
    };

    $classifyGroupPlans = function (array $groupLevels) {
        $plans = [
            'month' => null,
            'semi' => null,
            'year' => null,
        ];

        foreach ($groupLevels as $groupLevel) {
            if (!is_object($groupLevel)) {
                continue;
            }

            $number = isset($groupLevel->cycle_number) ? (int) $groupLevel->cycle_number : 0;
            $period = isset($groupLevel->cycle_period) ? strtolower((string) $groupLevel->cycle_period) : '';
            $isMonths = in_array($period, ['month', 'months'], true);
            $isYears = in_array($period, ['year', 'years'], true);

            if ($plans['month'] === null && $isMonths && $number === 1) {
                $plans['month'] = $groupLevel;
                continue;
            }

            if ($plans['semi'] === null && $isMonths && $number === 6) {
                $plans['semi'] = $groupLevel;
                continue;
            }

            if ($plans['year'] === null && $isYears && $number === 1) {
                $plans['year'] = $groupLevel;
            }
        }

        return $plans;
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

        return isset($level->allow_signups) ? (bool) $level->allow_signups : true;
    };

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
        @foreach ($levelGroups as $levelGroup)
            @php
                $groupId = isset($levelGroup->id) ? (int) $levelGroup->id : 0;
                if (!$groupId) {
                    continue;
                }

                $groupLevels = $resolveLevelsForGroup($groupId);
                if (empty($groupLevels)) {
                    continue;
                }

                // Keep current 3-frequency layout. Skip groups that do not match this model.
                $plans = $classifyGroupPlans($groupLevels);
                $mLevel = $plans['month'];
                $sLevel = $plans['semi'];
                $yLevel = $plans['year'];

                $base = $mLevel ?: ($sLevel ?: $yLevel);
                if (!$base) {
                    continue;
                }

                $groupName = isset($levelGroup->name) ? trim((string) $levelGroup->name) : '';
                $name = $groupName !== '' ? $groupName : ($base->name ?? '');
                $monthlyDescription = $mLevel && isset($mLevel->description) ? (string) $mLevel->description : '';
                $semiDescription = $sLevel && isset($sLevel->description) ? (string) $sLevel->description : '';
                $yearlyDescription = $yLevel && isset($yLevel->description) ? (string) $yLevel->description : '';

                $priceMonthly = $extractRecurringAmount($mLevel, 'month');
                $priceSemi = $extractRecurringAmount($sLevel, 'semi');
                $priceYearly = $extractRecurringAmount($yLevel, 'year');

                $allowMonthly = $isSignupAllowed($mLevel);
                $allowSemi = $isSignupAllowed($sLevel);
                $allowYearly = $isSignupAllowed($yLevel);

                if (!$allowMonthly && !$allowSemi && !$allowYearly) {
                    continue;
                }

                $monthlyLabel = 'Suscribete al plan mensual';
                $semiLabel = 'Suscribete al plan semestral';
                $yearlyLabel = 'Suscribete al plan anual';

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

            <x-pricing-package :id="$base->id" :name="$name" :monthly-description="$monthlyDescription"
                :semi-description="$semiDescription" :yearly-description="$yearlyDescription" :price-monthly="$priceMonthly"
                :price-semi="$priceSemi" :price-yearly="$priceYearly" :checkout-monthly-url="$checkoutMonthlyUrl"
                :checkout-semi-url="$checkoutSemiUrl" :checkout-yearly-url="$checkoutYearlyUrl"
                :monthly-label="$monthlyLabel" :semi-label="$semiLabel" :yearly-label="$yearlyLabel"
                :monthly-subscribed="$monthlySubscribed" :semi-subscribed="$semiSubscribed"
                :yearly-subscribed="$yearlySubscribed" />
        @endforeach
    </div>
</div>
