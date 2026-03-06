@props([
    'id' => 0,
    'name' => '',
    'monthlyDescription' => '',
    'semiDescription' => '',
    'yearlyDescription' => '',
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

  // Ahorro anual frente a 12×mensual (solo informativo)
  $savingsPercent = null;
  if ($hasMonthly && $hasYearly) {
      $annualReference = (float) $priceMonthly * 12;
      if ($annualReference > 0 && (float) $priceYearly < $annualReference) {
          $savingsPercent = (int) round((1 - (float) $priceYearly / $annualReference) * 100);
      }
  }

  // Ahorro semestral frente a 6×mensual (solo informativo)
  $semiSavingsPercent = null;
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

  $monthlyDescriptionHtml = trim((string) $monthlyDescription);
  $semiDescriptionHtml = trim((string) $semiDescription);
  $yearlyDescriptionHtml = trim((string) $yearlyDescription);

  if ($monthlyDescriptionHtml !== '' && function_exists('wpautop')) {
      $monthlyDescriptionHtml = wpautop($monthlyDescriptionHtml);
  }
  if ($semiDescriptionHtml !== '' && function_exists('wpautop')) {
      $semiDescriptionHtml = wpautop($semiDescriptionHtml);
  }
  if ($yearlyDescriptionHtml !== '' && function_exists('wpautop')) {
      $yearlyDescriptionHtml = wpautop($yearlyDescriptionHtml);
  }

  $plans = [];

  if ($hasMonthly) {
      $plans[] = [
          'title' => 'Plan mensual',
          'descriptionHtml' => $monthlyDescriptionHtml,
          'priceValue' => $priceMonthly,
          'priceSuffix' => '€/mes',
          'savingsPercent' => null,
          'checkoutUrl' => $checkoutMonthlyUrl,
          'buttonText' => $monthlyText,
          'buttonAria' => $monthlyAria,
          'buttonState' => $monthlyState,
          'buttonSubscribed' => $monthlyIsSubscribed,
          'buttonStateClass' => $monthlyIsSubscribed ? ' bg-morado2 text-morado5' : ' bg-morado3 text-white',
      ];
  }

  if ($hasSemi) {
      $plans[] = [
          'title' => 'Plan semestral',
          'descriptionHtml' => $semiDescriptionHtml,
          'priceValue' => $priceSemi,
          'priceSuffix' => '€/6 meses',
          'savingsPercent' => $semiSavingsPercent,
          'checkoutUrl' => $checkoutSemiUrl,
          'buttonText' => $semiText,
          'buttonAria' => $semiAria,
          'buttonState' => $semiState,
          'buttonSubscribed' => $semiIsSubscribed,
          'buttonStateClass' => $semiIsSubscribed ? ' bg-morado2 text-morado5' : ' bg-morado3 text-white',
      ];
  }

  if ($hasYearly) {
      $plans[] = [
          'title' => 'Plan anual',
          'descriptionHtml' => $yearlyDescriptionHtml,
          'priceValue' => $priceYearly,
          'priceSuffix' => '€/año',
          'savingsPercent' => $savingsPercent,
          'checkoutUrl' => $checkoutYearlyUrl,
          'buttonText' => $yearlyText,
          'buttonAria' => $yearlyAria,
          'buttonState' => $yearlyState,
          'buttonSubscribed' => $yearlyIsSubscribed,
          'buttonStateClass' => $yearlyIsSubscribed
              ? ' is-subscribed bg-morado2 text-morado5'
              : ' is-available bg-morado3 text-white',
      ];
  }
@endphp

<div class="pricing-package mx-auto mb-6 mt-10 flex max-w-4xl flex-col items-center rounded-lg bg-white/5 p-6">
  <h3 class="text-2xl font-semibold">{{ $name }}</h3>

  <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
    @foreach ($plans as $plan)
      <x-pricing-plan-card :title="$plan['title']" :description-html="$plan['descriptionHtml']" :price-value="$plan['priceValue']" :price-suffix="$plan['priceSuffix']" :savings-percent="$plan['savingsPercent']"
        :checkout-url="$plan['checkoutUrl']" :button-text="$plan['buttonText']" :button-aria="$plan['buttonAria']" :button-state="$plan['buttonState']" :button-subscribed="$plan['buttonSubscribed']" :button-state-class="$plan['buttonStateClass']" />
    @endforeach
  </div>

  {{-- Fallback cuando no hay URLs específicas --}}
  @if (!$checkoutMonthlyUrl && !$checkoutSemiUrl && !$checkoutYearlyUrl)
    @php
      $fallbackUrl = function_exists('pmpro_url')
          ? pmpro_url('checkout', '?pmpro_level=' . (int) $id)
          : home_url('/membership-checkout/?level=' . (int) $id);
    @endphp
    <div class="mt-6 w-full sm:w-auto">
      <a href="{{ $fallbackUrl }}"
        class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
        Hazte miembro
      </a>
    </div>
  @endif
</div>
