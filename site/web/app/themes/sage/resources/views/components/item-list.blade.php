@props([
    'items' => [],
    'itemClass' => 'flex items-start gap-3',
    'titleClass' => '',
    'textClass' => '',
    'iconClass' => 'h-5 w-5 shrink-0',
])

@php
  $normalizedItems = collect($items)
      ->map(function ($item) {
          if (is_string($item)) {
              $text = trim($item);

              return $text !== '' ? ['text' => $text, 'icon' => null] : null;
          }

          if (is_array($item) || is_object($item)) {
              $data = (array) $item;
              $text = trim((string) ($data['text'] ?? ''));

              if ($text === '') {
                  return null;
              }

              return [
                  'title' => !empty($data['title']) ? trim((string) $data['title']) : '',
                  'text' => $text,
                  'icon' => !empty($data['icon']) ? (string) $data['icon'] : null,
                  'itemClass' => !empty($data['itemClass']) ? (string) $data['itemClass'] : '',
                  'titleClass' => !empty($data['titleClass']) ? (string) $data['titleClass'] : '',
                  'textClass' => !empty($data['textClass']) ? (string) $data['textClass'] : '',
                  'iconClass' => !empty($data['iconClass']) ? (string) $data['iconClass'] : '',
              ];
          }

          return null;
      })
      ->filter()
      ->values();
@endphp

@if ($normalizedItems->isNotEmpty())
  <ul {{ $attributes->class(['item-list flex flex-col gap-3']) }}>
    @foreach ($normalizedItems as $item)
      @php
        $resolvedItemClass = trim($itemClass . ' ' . ($item['itemClass'] ?? ''));
        $resolvedTitleClass = trim($titleClass . ' ' . ($item['titleClass'] ?? ''));
        $resolvedTextClass = trim($textClass . ' ' . ($item['textClass'] ?? ''));
        $resolvedIconClass = trim($iconClass . ' ' . ($item['iconClass'] ?? ''));
      @endphp

      <li class="{{ $resolvedItemClass }}">
        @if (!empty($item['icon']))
          <x-dynamic-component :component="$item['icon']" class="{{ $resolvedIconClass }}" aria-hidden="true" />
        @endif
        @if (!empty($item['title']))
          <div class="flex-1">
            <span class="{{ $resolvedTitleClass }}">{{ $item['title'] }}</span>
            <span class="{{ trim('block ' . $resolvedTextClass) }}">{{ $item['text'] }}</span>
          </div>
        @else
          <span class="{{ $resolvedTextClass }}">{{ $item['text'] }}</span>
        @endif
      </li>
    @endforeach
  </ul>
@endif
