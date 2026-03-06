@props([
    'href' => '#',
    'text' => '',
    'clases' => 'bg-morado5/90',
    'icon' => null,
])

<a href="{{ $href }}"
  {{ $attributes->class([
      'cta',
      'focus-visible:ring-morado2 inline-flex min-w-52 flex-col items-center justify-center rounded-lg px-7 pb-3 pt-6 text-center font-sans text-2xl transition focus:outline-none focus-visible:ring-2 colores-hover-self overflow-hidden',
      $clases,
  ]) }}>
  {{ $text }}
  @if (!empty($icon))
    <x-dynamic-component :component="$icon" class="h-14 w-14" />
  @endif
</a>
