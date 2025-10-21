@php $interactive = $interactive ?? true @endphp

<ul class="list-disc space-y-3 pl-5">
    @foreach ($items as $item)
        @php
            $hasAnchor = !empty($item['anchor']);
            $titleClasses =
                $interactive && $hasAnchor
                    ? 'text-morado2 hover:text-blanco cursor-pointer transition font-semibold'
                    : 'text-morado2';
        @endphp
        <li class="bg-morado5/50 border-morado3/50 rounded-sm border px-4 py-2">
            <div class="flex flex-wrap items-center gap-3">
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

            @if ($item['description'])
                <p class="text-morado1/80 mt-2 text-sm">
                    {{ $item['description'] }}
                </p>
            @endif

            @if (!empty($item['children']))
                <div class="mt-3 pl-4">
                    @include('partials.lesson-subindex-children', [
                        'items' => $item['children'],
                        'interactive' => $interactive,
                    ])
                </div>
            @endif
        </li>
    @endforeach
</ul>
