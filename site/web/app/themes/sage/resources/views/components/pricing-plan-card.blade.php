@props([
    'title' => '',
    'descriptionHtml' => '',
    'priceValue' => null,
    'priceSuffix' => '',
    'priceNote' => '',
    'savingsPercent' => null,
    'checkoutUrl' => null,
    'buttonText' => '',
    'buttonAria' => '',
    'buttonState' => 'available',
    'buttonSubscribed' => false,
])

@php
  $descriptionHtml = trim((string) $descriptionHtml);
  $hasPrice = !is_null($priceValue) && $priceValue !== '';
  $formattedPrice = $hasPrice ? number_format((float) $priceValue, 2) : null;
  $priceNote = trim((string) $priceNote);
@endphp

<div class="bg-negro/70 flex h-full flex-col justify-between rounded-lg pb-4">

  <div>
    <h4 class="border-gris4 w-full border-b p-4 pb-3 text-center text-lg text-white/90">{{ $title }}</h4>

    @if ($hasPrice)
      <div class="mt-8 min-h-[70px] w-full text-center text-lg font-bold">
        <div>{{ $formattedPrice }}<span class="text-gris3 font-normal"> {{ $priceSuffix }}</span></div>
        @if (!is_null($savingsPercent) && (int) $savingsPercent > 0)
          <div class="mt-2 inline-block rounded-sm border border-red-600 px-3 font-thin text-red-600">
            Ahorra {{ (int) $savingsPercent }}%
          </div>
        @endif
        @if ($priceNote !== '')
          <div class="mx-4 mt-2 inline-block rounded-sm border border-red-600 p-2 text-sm font-thin text-red-600">
            {{ $priceNote }}
          </div>
        @endif
      </div>
    @endif

    @if ($descriptionHtml !== '')
      <div class="px-4 pt-4 text-sm font-light text-white/50 [&_p:last-child]:mb-0 [&_p]:mb-3">
        {!! $descriptionHtml !!}
      </div>
    @endif

  </div>

  @if ($checkoutUrl)
    <a href="{{ $checkoutUrl }}"
      class="text-sol/80 hover:text-morado5 colores-hover-self mx-4 mt-4 inline-flex cursor-pointer items-center justify-center overflow-hidden rounded bg-white/5 px-4 py-2 text-center focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
      aria-label="{{ $buttonAria }}" data-subscribed="{{ $buttonSubscribed ? 'true' : 'false' }}"
      data-state="{{ $buttonState }}">
      {{ $buttonText }}
    </a>
  @endif
</div>
