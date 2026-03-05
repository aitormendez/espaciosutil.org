@props([
    'primaryHref' => '#planes',
    'primaryText' => 'Elegir plan',
    'secondaryHref' => home_url('/leccion-gratuita/'),
    'secondaryText' => 'Ver lección gratuita',
])

<div class="membership-hero-ctas flex flex-col gap-4 sm:flex-row sm:items-center">
  <a href="{{ $primaryHref }}"
    class="bg-morado1 text-morado5 focus-visible:ring-morado2 inline-flex min-w-52 items-center justify-center rounded border border-transparent px-7 py-3 text-center font-sans text-2xl font-semibold transition hover:bg-white focus:outline-none focus-visible:ring-2">
    {{ $primaryText }}
    <svg class="icon-show text-sol h-6 w-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
      stroke-linejoin="round" aria-hidden="true">
      <path stroke="none" d="M0 0h24v24H0z" fill="none" />
      <path d="M17 13v-6l-5 4l-5 -4v6l5 4l5 -4" />
    </svg>
  </a>

  <a href="{{ $secondaryHref }}"
    class="border-morado2/50 bg-morado5/30 text-gris1 hover:border-morado1 hover:bg-morado5/55 focus-visible:ring-morado2 inline-flex min-w-52 items-center justify-center rounded border px-7 py-3 text-center font-sans text-2xl font-medium transition focus:outline-none focus-visible:ring-2">
    {{ $secondaryText }}
  </a>
</div>
