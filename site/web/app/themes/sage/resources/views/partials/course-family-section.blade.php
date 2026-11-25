@php
  // Complete literal classes keep both presentations visible to Tailwind's scanner.
  $familyToneClasses =
      ($group['text_color'] ?? 'white') === 'black'
          ? 'text-negro [--course-active:rgb(0_0_0/0.12)] [--course-rule:rgb(0_0_0/0.3)]'
          : 'text-blanco [--course-active:rgb(255_255_255/0.14)] [--course-rule:rgb(255_255_255/0.3)]';
@endphp
<section
  class="course-family course-family--{{ $group['key'] }} {{ $familyToneClasses }} border-t-(--course-rule) bg-(--course-family-bg,var(--color-cde)) relative border-t"
  data-course-family="{{ $group['key'] }}" @if (!empty($group['background_color']) || empty($group['legacy']) && ($group['text_color'] ?? '') === 'black') data-family-palette @endif
  style="--course-family-bg: {{ $group['background_color'] ?? 'var(' . $group['color_token'] . ')' }}"
  aria-labelledby="family-heading-{{ $group['key'] }}">
  <div
    class="course-family-grid max-w-400 mx-auto grid min-h-48 w-full grid-cols-1 gap-7 px-[clamp(1.25rem,3.5vw,3.5rem)] py-8 md:grid-cols-[minmax(10rem,0.85fr)_minmax(12rem,1fr)_minmax(0,3fr)] md:gap-x-[clamp(1.5rem,3vw,3rem)] md:pb-4">
    <header class="course-family__identity flex min-w-0 items-center gap-4 md:block">
      @include('partials.cde-family-icon', [
          'family' => $group,
          'iconClass' => 'course-family__icon block h-15 w-14 flex-none md:mb-4 md:h-24 md:w-22',
      ])
      <h2 class="m-0 text-[1.375rem] font-bold leading-tight" id="family-heading-{{ $group['key'] }}">{{ $group['label'] }}</h2>
    </header>

    <nav class="course-family__series min-w-0" aria-labelledby="family-series-{{ $group['key'] }}">
      <h3 class="sr-only" id="family-series-{{ $group['key'] }}">Series: {{ $group['label'] }}</h3>
      <ul class="course-family__series-list m-0 list-none p-0">
        @foreach ($group['series'] as $series)
          @php
            $seriesTermId = $series['term_id'] ?? null;
            $seriesBlocks = $series['blocks'] ?? [];
            $seriesButtonId = 'serie-toggle-' . $group['key'] . '-' . ($seriesTermId ?: 'missing') . '-' . $loop->index;
            $seriesPanelId = "{$seriesButtonId}-panel";
          @endphp
          <li class="serie-accordion-item">
            <button type="button"
              class="serie-accordion-toggle focus-visible:outline-offset-3 wrap-anywhere hover:bg-(--course-active) md:min-h-7.5 flex min-h-11 w-full cursor-pointer items-center justify-between gap-3 px-2 py-1 text-left font-[inherit] text-[0.9375rem] font-semibold leading-normal text-inherit focus-visible:outline-2 focus-visible:outline-current md:px-0 md:text-base"
              data-series-term="{{ $seriesTermId }}" aria-expanded="false" aria-controls="{{ $seriesPanelId }}"
              id="{{ $seriesButtonId }}">
              <span class="md:pl-2" data-series-title>{{ $series['name'] }}</span>
              <span class="serie-accordion-icon flex-none md:pr-2" aria-hidden="true">+</span>
            </button>
            <div id="{{ $seriesPanelId }}" class="serie-accordion-panel hidden" role="region"
              aria-labelledby="{{ $seriesButtonId }}">
              @if (!empty($seriesBlocks))
                <ul
                  class="course-family__blocks border-l-(--course-rule) mb-3 ml-2 mr-0 mt-1.5 list-none border-l py-0 pl-2 pr-0">
                  @foreach ($seriesBlocks as $block)
                    <li>
                      <button type="button"
                        class="serie-cde-button focus-visible:outline-offset-3 wrap-anywhere hover:bg-(--course-active) md:min-h-7.5 [&.is-active]:bg-(--course-active) flex min-h-11 w-full cursor-pointer items-center px-2 py-1 text-left font-[inherit] text-[0.9375rem] leading-normal text-inherit focus-visible:outline-2 focus-visible:outline-current disabled:cursor-wait md:px-0 md:pl-2 md:text-sm"
                        data-post-id="{{ $block['post_id'] }}" data-block-term="{{ $block['term_id'] }}"
                        aria-controls="topics-{{ $group['key'] }}">
                        {{ $block['name'] }}
                      </button>
                    </li>
                  @endforeach
                </ul>
              @else
                <p class="course-family__empty p-2 text-sm leading-normal">No hay bloques disponibles.</p>
              @endif
            </div>
          </li>
        @endforeach
      </ul>
    </nav>

    <div class="course-family__topics min-w-0">
      <h3
        class="course-family__topics-heading mb-4 scroll-mt-[calc(2rem+var(--wp-admin--admin-bar--height,0px))] text-[1.375rem] font-normal md:sr-only"
        id="topics-heading-{{ $group['key'] }}" data-course-topics-heading>
        Temas<span class="sr-only">: {{ $group['label'] }}</span>
      </h3>
      <div id="topics-{{ $group['key'] }}"
        class="course-index prose-cde wrap-anywhere text-base leading-normal text-inherit" data-course-topics
        role="region" aria-live="polite" aria-labelledby="topics-heading-{{ $group['key'] }}">
        <p class="course-family__prompt m-0 pt-1 font-light">Selecciona un bloque de la serie para ver sus temas.</p>
      </div>
    </div>
  </div>
</section>
