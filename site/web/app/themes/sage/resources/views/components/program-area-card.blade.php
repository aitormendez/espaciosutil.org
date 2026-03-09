@props([
    'title' => '',
    'description' => '',
    'icon' => 'tabler-sitemap-filled',
])

<article class="rounded border border-white/30 bg-black/30 p-6 font-sans font-light">
  <header class="text-cde-light mb-4 text-center">
    <x-dynamic-component :component="$icon" class="inline h-14 w-14" />
    <h3 class="text-2xl">{{ $title }}</h3>
  </header>
  <p class="text-white/70">{{ $description }}</p>
</article>
