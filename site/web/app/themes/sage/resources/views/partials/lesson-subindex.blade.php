@php
  $interactive = $interactive ?? true;
  $linkTitles = $link_titles ?? $interactive;
  $enableSeekButtons = $enable_seek_buttons ?? $interactive;
  $plainTitleClasses = $plain_title_classes ?? 'text-morado2 tracking-wide';
  $isNested = $is_nested ?? false;
  $padMap = [
      1 => 'pl-0',
      2 => 'pl-4',
      3 => 'pl-8',
      4 => 'pl-12',
  ];
  $marginMap = [
      1 => 'ml-0',
      2 => 'ml-6',
      3 => 'ml-10',
      4 => 'ml-14',
  ];
@endphp

<ol>
  @foreach ($items as $item)
    @php
      $level = max(1, min(4, (int) ($item['level'] ?? 1)));
      $hasAnchor = !empty($item['anchor']);
      $shouldLinkTitle = $linkTitles && $hasAnchor;
      $indentPad = $padMap[$level] ?? 'pl-4';
      $indentMargin = !$isNested && $level > 1 ? $marginMap[$level] ?? 'ml-6' : 'ml-0';
      $titleClasses = $shouldLinkTitle
          ? 'text-morado2 hover:text-blanco cursor-pointer tracking-wide transition'
          : $plainTitleClasses;
    @endphp
    <li class="border-b last:border-none" data-level="{{ $level }}">
      <div class="{{ $indentMargin }} {{ $indentPad }} grid grid-cols-[minmax(0,1fr)_auto] items-start gap-x-3 py-2">
        @if ($shouldLinkTitle)
          <a href="#{{ $item['anchor'] }}" class="{{ $titleClasses }} min-w-0">
            {{ $item['title'] }}
          </a>
        @else
          <p class="{{ $titleClasses }} min-w-0">
            {{ $item['title'] }}
          </p>
        @endif

        @if ($enableSeekButtons && $item['timecode'])
          <button type="button"
            class="border-morado2/30 bg-morado2/20 text-morado1 hover:border-morado1/80 hover:text-blanco col-start-2 h-full min-w-[65px] cursor-pointer self-start justify-self-end whitespace-nowrap rounded border px-3 py-1 text-xs font-bold uppercase tracking-wide transition lg:min-w-[80px]"
            data-video-seek="{{ $item['timecode']['seconds'] ?? null }}"
            data-video-time-label="{{ $item['timecode']['label'] ?? null }}"
            aria-label="Ir al minuto {{ $item['timecode']['label'] ?? '' }}"
            title="Ir al minuto {{ $item['timecode']['label'] ?? '' }}">
            {{ $item['timecode']['label'] }}
          </button>
        @endif
      </div>

      @if (!empty($item['children']))
        <div class="">
          @include('partials.lesson-subindex-children', [
              'items' => $item['children'],
              'interactive' => $interactive,
              'link_titles' => $linkTitles,
              'enable_seek_buttons' => $enableSeekButtons,
              'plain_title_classes' => $plainTitleClasses,
              'is_nested' => true,
          ])
        </div>
      @endif
    </li>
  @endforeach
</ol>
