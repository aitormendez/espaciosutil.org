@props([
    'title' => '',
    'description' => '',
    'icon' => 'tabler-sitemap-filled',
    'iconSecondary' => null,
])

<article class="rounded border border-white/30 bg-black/30 p-6 font-sans font-light">
  <header class="text-cde-light mb-4 text-center">
    <div class="mb-2 flex justify-center gap-3">
      <x-dynamic-component :component="$icon" class="h-14 w-14" />
      @if (!empty($iconSecondary))
        <x-dynamic-component :component="$iconSecondary" class="h-14 w-14" />
      @endif
    </div>
    <h3 class="text-2xl">{{ $title }}</h3>
  </header>
  <p class="text-white/70">{{ $description }}</p>
</article>
