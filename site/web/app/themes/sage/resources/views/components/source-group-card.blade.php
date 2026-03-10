@props([
    'title' => '',
    'items' => [],
    'highlighted' => false,
])

@php
  $normalizedItems = collect($items)->map(fn($item) => trim((string) $item))->filter()->values();
@endphp

<article @class([
    'rounded border p-6 lg:p-8 font-sans font-light',
    'border-white/50 bg-black/50' => $highlighted,
    'border-white/20 bg-white/5' => !$highlighted,
])>
  <h3 @class([
      'text-2xl',
      'text-white/90' => $highlighted,
      'text-white/70' => !$highlighted,
  ])>{{ $title }}</h3>

  @if ($normalizedItems->isNotEmpty())
    <ul class="mt-6 flex flex-col gap-3">
      @foreach ($normalizedItems as $item)
        <li @class([
            'border-t pt-3 text-lg',
            'border-white/15 text-white/80' => $highlighted,
            'border-white/10 text-white/60' => !$highlighted,
            'first:border-t-0 first:pt-0',
        ])>
          {{ $item }}
        </li>
      @endforeach
    </ul>
  @endif
</article>
