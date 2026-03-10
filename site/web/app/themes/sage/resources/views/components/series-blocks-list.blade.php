@props([
    'series' => [],
])

@if (!empty($series))
  <ul {{ $attributes->class(['space-y-3 px-6 md:px-0']) }}>
    @foreach ($series as $seriesItem)
      @php
        $seriesBlocks = $seriesItem['blocks'] ?? [];
        $blocksCount = count($seriesBlocks);
      @endphp
      <li class="bg-negro/30 border-blanco border-t text-lg last:border-b md:text-2xl">
        <h3 class="mx-auto flex max-w-3xl items-center justify-between px-3 py-3 text-white/90 md:px-0">
          <span>{{ $seriesItem['name'] }}</span>
          <span class="text-white/30">{{ $blocksCount }} {{ $blocksCount === 1 ? 'bloque' : 'bloques' }}</span>
        </h3>

        @if (!empty($seriesBlocks))
          <ul class="list-disc">
            @foreach ($seriesBlocks as $block)
              @php
                $lessonsCount = (int) ($block['lessons_count'] ?? 0);
              @endphp
              <li
                class="border-blanco/30 text-blanco/30 mx-auto flex max-w-3xl items-center justify-between gap-3 border-t py-2 pr-3 md:pr-0">
                <span class="flex-1 text-left font-light">{{ $block['name'] }}</span>
                <span class="whitespace-nowrap text-right font-light text-white/30">{{ $lessonsCount }}
                  {{ $lessonsCount === 1 ? 'lección' : 'lecciones' }}</span>
              </li>
            @endforeach
          </ul>
        @else
          <p class="mx-auto max-w-3xl px-4 pb-4 text-sm text-white/60">Esta serie aún no tiene bloques
            disponibles.</p>
        @endif
      </li>
    @endforeach
  </ul>
@else
  <p class="rounded-xs mx-auto mt-4 max-w-3xl bg-white/5 px-4 py-3 text-sm text-white/70">No hay series
    disponibles todavía.</p>
@endif
