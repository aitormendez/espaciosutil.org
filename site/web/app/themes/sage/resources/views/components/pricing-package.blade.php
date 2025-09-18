@props([
    'id' => 0,
    'name' => '',
    'description' => '',
    'image' => null,
    'priceMonthly' => null,
    'priceYearly' => null,
    'checkoutMonthlyUrl' => null,
    'checkoutYearlyUrl' => null,
])

<div class="pricing-package rounded-xs mb-6 flex flex-col border border-gray-200 bg-white p-6 shadow-md">
    @if ($image)
        <img src="{{ $image }}" alt="{{ $name }}" class="mb-4 h-40 w-full rounded-xl object-cover">
    @endif

    <h3 class="mb-2 text-xl font-semibold">{{ $name }}</h3>
    @if ($description)
        <p class="mb-4 text-gray-600">{{ $description }}</p>
    @endif

    <div class="mt-auto">
        @if (!is_null($priceMonthly) || !is_null($priceYearly))
            <div class="mb-4">
                @if (!is_null($priceMonthly))
                    <p class="text-lg font-bold">
                        €{{ number_format((float) $priceMonthly, 2) }} <span
                            class="text-sm font-normal text-gray-600">/mes</span>
                    </p>
                @endif
                @if (!is_null($priceYearly))
                    <p class="text-lg font-bold">
                        €{{ number_format((float) $priceYearly, 2) }} <span
                            class="text-sm font-normal text-gray-600">/año</span>
                    </p>
                @endif
            </div>
        @endif

        <div class="flex gap-3">
            @if ($checkoutMonthlyUrl)
                <a href="{{ $checkoutMonthlyUrl }}"
                    class="flex-1 rounded-xl bg-indigo-600 px-4 py-2 text-center text-white hover:bg-indigo-700">
                    Plan mensual
                </a>
            @endif
            @if ($checkoutYearlyUrl)
                <a href="{{ $checkoutYearlyUrl }}"
                    class="flex-1 rounded-xl bg-gray-900 px-4 py-2 text-center text-white hover:bg-black">
                    Plan anual
                </a>
            @endif
            @if (!$checkoutMonthlyUrl && !$checkoutYearlyUrl)
                <a href="{{ home_url('/membership-checkout/?level=' . $id) }}"
                    class="w-full rounded-xl bg-indigo-600 px-4 py-2 text-center text-white hover:bg-indigo-700">
                    Hazte miembro
                </a>
            @endif
        </div>
    </div>
</div>
