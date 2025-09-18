@props([
    'id' => 0,
    'name' => '',
    'description' => '',
    'image' => null,
    'imageSrcset' => null,
    'imageSizes' => null,
    'priceMonthly' => null,
    'priceYearly' => null,
    'checkoutMonthlyUrl' => null,
    'checkoutYearlyUrl' => null,
    'monthlyLabel' => null,
    'yearlyLabel' => null,
    'monthlySubscribed' => false,
    'yearlySubscribed' => false,
    'priceSemi' => null,
    'checkoutSemiUrl' => null,
    'semiLabel' => null,
    'semiSubscribed' => false,
])

@php
    $hasMonthly = !is_null($priceMonthly) && $priceMonthly !== '';
    $hasYearly = !is_null($priceYearly) && $priceYearly !== '';
    $hasSemi = !is_null($priceSemi) && $priceSemi !== '';

    $format = function ($n) {
        return number_format((float) $n, 2);
    };

    // Ahorro anual frente a 12×mensual (solo informativo)
    $savingsPercent = null;
    $annualReference = null;
    if ($hasMonthly && $hasYearly) {
        $annualReference = (float) $priceMonthly * 12;
        if ($annualReference > 0 && (float) $priceYearly < $annualReference) {
            $savingsPercent = (int) round((1 - (float) $priceYearly / $annualReference) * 100);
        }
    }

    // Ahorro semestral frente a 6×mensual (solo informativo)
    $semiSavingsPercent = null;
    $semiReference = null;
    if ($hasMonthly && $hasSemi) {
        $semiReference = (float) $priceMonthly * 6;
        if ($semiReference > 0 && (float) $priceSemi < $semiReference) {
            $semiSavingsPercent = (int) round((1 - (float) $priceSemi / $semiReference) * 100);
        }
    }

    $monthlyText = $monthlyLabel ?? 'Suscríbete al plan mensual';
    $yearlyText = $yearlyLabel ?? 'Suscríbete al plan anual';
    $semiText = $semiLabel ?? 'Suscríbete al plan semestral';

    $monthlyAria = $monthlyText === 'Suscrito' ? 'Ver cuenta de membresía' : 'Suscribirse al plan mensual de ' . $name;
    $yearlyAria = $yearlyText === 'Suscrito' ? 'Ver cuenta de membresía' : 'Suscribirse al plan anual de ' . $name;
    $semiAria = $semiText === 'Suscrito' ? 'Ver cuenta de membresía' : 'Suscribirse al plan semestral de ' . $name;

    $monthlyIsSubscribed = (bool) $monthlySubscribed;
    $yearlyIsSubscribed = (bool) $yearlySubscribed;
    $semiIsSubscribed = (bool) $semiSubscribed;

    $monthlyState = $monthlyIsSubscribed ? 'subscribed' : 'available';
    $yearlyState = $yearlyIsSubscribed ? 'subscribed' : 'available';
    $semiState = $semiIsSubscribed ? 'subscribed' : 'available';
@endphp

<div class="pricing-package not-prose rounded-xs mb-6 border border-white p-6">
    <div class="flex flex-col flex-wrap gap-6 sm:flex-row">
        {{-- Bloque visual (opcional) --}}
        @if ($image)
            <div class="w-full shrink-0 sm:w-[calc(50%-0.75rem)]">
                <img src="{{ $image }}" alt="{{ $name }}" class="rounded-xs w-full object-cover"
                    @if ($imageSrcset) srcset="{{ $imageSrcset }}"
                        sizes="{{ $imageSizes ? $imageSizes : '(min-width: 640px) 20vw, 100vw' }}" @endif>
            </div>
        @endif

        {{-- Identidad y descripción --}}
        <div class="w-full sm:w-[calc(50%-0.75rem)]">
            <h3 class="mb-1 text-xl font-semibold">{{ $name }}</h3>
            @if ($description)
                <p class="text-gray-600">{!! $description !!}</p>
            @endif
        </div>

        {{-- Bloque Mensual --}}
        @if ($hasMonthly)
            <div class="my-10 flex flex-col items-center sm:w-[calc(33%-1rem)]">
                <div class="">
                    <div class="text-lg font-bold">€{{ $format($priceMonthly) }} <span
                            class="font-normal text-gray-600">/mes</span></div>
                </div>

                @if ($checkoutMonthlyUrl)
                    <a href="{{ $checkoutMonthlyUrl }}"
                        class="hover:bg-morado5 rounded-xs focus-visible:ring-gray-700{{ $monthlyIsSubscribed ? ' bg-morado2 text-morado5' : ' bg-morado3 text-white' }} mt-4 inline-flex cursor-pointer items-center justify-center border border-transparent px-4 py-2 hover:border-white hover:text-white focus:outline-none focus-visible:ring-2"
                        aria-label="{{ $monthlyAria }}" data-subscribed="{{ $monthlyIsSubscribed ? 'true' : 'false' }}"
                        data-state="{{ $monthlyState }}">
                        {{ $monthlyText }}
                    </a>
                @endif
            </div>
        @endif

        {{-- Bloque Semestral --}}
        @if ($hasSemi)
            <div class="my-10 flex flex-col items-center sm:w-[calc(33%-1rem)]">
                <div class="text-center">
                    <div class="text-lg font-bold">€{{ $format($priceSemi) }} <span
                            class="font-normal text-gray-600">/6&nbsp;meses</span>
                        @if (!is_null($semiSavingsPercent) && $semiSavingsPercent > 0)
                            <span class="rounded-sm border border-red-600 px-3 font-thin text-red-600">Ahorra
                                {{ $semiSavingsPercent }}%</span>
                        @endif
                    </div>
                </div>

                @if ($checkoutSemiUrl)
                    <a href="{{ $checkoutSemiUrl }}"
                        class="hover:bg-morado5 rounded-xs focus-visible:ring-gray-700{{ $semiIsSubscribed ? ' bg-morado2 text-morado5' : ' bg-morado3 text-white' }} mt-4 inline-flex cursor-pointer items-center justify-center border border-transparent px-4 py-2 hover:border-white hover:text-white focus:outline-none focus-visible:ring-2"
                        aria-label="{{ $semiAria }}" data-subscribed="{{ $semiIsSubscribed ? 'true' : 'false' }}"
                        data-state="{{ $semiState }}">
                        {{ $semiText }}
                    </a>
                @endif
            </div>
        @endif

        {{-- Bloque Anual --}}
        @if ($hasYearly)
            <div class="my-10 flex flex-col items-center sm:w-[calc(33%-1rem)]">
                <div class="text-center">
                    <div class="flex text-lg font-bold">€{{ $format($priceYearly) }} <span
                            class="mr-3 font-normal text-gray-600">/año</span>
                        @if (!is_null($savingsPercent) && $savingsPercent > 0)
                            <span class="rounded-sm border border-red-600 px-3 font-thin text-red-600">Ahorra
                                {{ $savingsPercent }}%</span>
                        @endif
                    </div>
                </div>

                @if ($checkoutYearlyUrl)
                    <a href="{{ $checkoutYearlyUrl }}"
                        class="bg-morado3 hover:bg-morado5 rounded-xs focus-visible:ring-gray-700{{ $yearlyIsSubscribed ? ' is-subscribed bg-morado2 text-morado5' : ' is-available' }} mt-4 inline-flex items-center justify-center border border-transparent px-4 py-2 text-white hover:border-white focus:outline-none focus-visible:ring-2"
                        aria-label="{{ $yearlyAria }}"
                        data-subscribed="{{ $yearlyIsSubscribed ? 'true' : 'false' }}"
                        data-state="{{ $yearlyState }}">
                        {{ $yearlyText }}
                    </a>
                @endif
            </div>
        @endif

        {{-- Fallback cuando no hay URLs específicas --}}
        @if (!$checkoutMonthlyUrl && !$checkoutSemiUrl && !$checkoutYearlyUrl)
            @php
                $fallbackUrl = function_exists('pmpro_url')
                    ? pmpro_url('checkout', '?pmpro_level=' . (int) $id)
                    : home_url('/membership-checkout/?level=' . (int) $id);
            @endphp
            <div class="w-full sm:w-auto">
                <a href="{{ $fallbackUrl }}"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                    Hazte miembro
                </a>
            </div>
        @endif
    </div>
</div>
