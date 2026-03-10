@props([
    'title' => '',
    'text' => '',
    'textPrefix' => '',
    'items' => [],
])

@php
  $normalizedItems = collect($items)
      ->map(function ($item) {
          if (is_array($item) || is_object($item)) {
              $data = (array) $item;

              return trim((string) ($data['name'] ?? ''));
          }

          return trim((string) $item);
      })
      ->filter()
      ->values()
      ->all();

  $formattedItems = match (count($normalizedItems)) {
      0 => '',
      1 => $normalizedItems[0],
      2 => $normalizedItems[0] . ' y ' . $normalizedItems[1],
      default => implode(', ', array_slice($normalizedItems, 0, -1)) . ' y ' . end($normalizedItems),
  };

  $resolvedText = trim($text !== '' ? $text : trim($textPrefix . ' ' . $formattedItems . '.'));
  $resolvedText = str_replace([' de El ', ' de el '], ' del ', $resolvedText);
@endphp

<div class="bg-morado5/90 prose-cde mx-6 rounded border border-white/30 pb-2 lg:mx-auto">
  <div class="bg-cde/50 w-full px-4 py-2 text-center uppercase text-white/50">{{ $title }}</div>
  <p class="mx-6">{{ $resolvedText }}</p>
</div>
