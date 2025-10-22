@php
    $interactive = $interactive ?? true;
    $isNested = $is_nested ?? false;
    $padMap = [
        1 => 'pl-4',
        2 => 'pl-8',
        3 => 'pl-12',
        4 => 'pl-16',
    ];
    $marginMap = [
        1 => 'ml-0',
        2 => 'ml-6',
        3 => 'ml-10',
        4 => 'ml-14',
    ];
@endphp

<ol class="list-decimal space-y-3 pl-5">
    @foreach ($items as $item)
        @php
            $level = max(1, min(4, (int) ($item['level'] ?? 1)));
            $hasAnchor = !empty($item['anchor']);
            $indentPad = $padMap[$level] ?? 'pl-4';
            $indentMargin = !$isNested && $level > 1 ? ($marginMap[$level] ?? 'ml-6') : 'ml-0';
            $titleClasses =
                $interactive && $hasAnchor
                    ? 'text-morado2 hover:text-blanco cursor-pointer tracking-wide transition'
                    : 'text-morado2 tracking-wide';
        @endphp
        <li class="bg-morado5/60 rounded-sm py-4 pr-4 {{ $indentMargin }}" data-level="{{ $level }}">
            <div class="flex flex-wrap items-center gap-3 {{ $indentPad }}">
                @if ($interactive && $hasAnchor)
                    <a href="#{{ $item['anchor'] }}" class="{{ $titleClasses }}">
                        {{ $item['title'] }}
                    </a>
                @else
                    <p class="{{ $titleClasses }}">
                        {{ $item['title'] }}
                    </p>
                @endif

                @if ($interactive && $item['timecode'])
                    <button type="button"
                        class="border-morado2/30 bg-morado2/20 text-morado1 hover:border-morado1/80 hover:text-blanco cursor-pointer rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-wide transition"
                        data-video-seek="{{ $item['timecode']['seconds'] ?? null }}"
                        data-video-time-label="{{ $item['timecode']['label'] ?? null }}"
                        aria-label="Ir al minuto {{ $item['timecode']['label'] ?? '' }}"
                        title="Ir al minuto {{ $item['timecode']['label'] ?? '' }}">
                        {{ $item['timecode']['label'] }}
                    </button>
                @endif
            </div>

            @if (!empty($item['children']))
                <div class="mt-3 pl-4">
                    @include('partials.lesson-subindex-children', [
                        'items' => $item['children'],
                        'interactive' => $interactive,
                        'is_nested' => true,
                    ])
                </div>
            @endif
        </li>
    @endforeach
</ol>
