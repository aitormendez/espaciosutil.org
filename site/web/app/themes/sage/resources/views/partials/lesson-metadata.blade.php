@php
  $family = $metadata['family'] ?? null;
  $bandColor = $family['background_color'] ?? 'var(' . ($family['color_token'] ?? '--color-cde') . ')';
  $bandInk = ($family['text_color'] ?? 'white') === 'black' ? 'text-black' : 'text-white';
@endphp
@if ($family || !empty($metadata['series_name']) || !empty($metadata['production']) || is_user_logged_in())
  <div class="lesson-metadata w-full bg-(--lesson-band-bg) {{ $bandInk }}" style="--lesson-band-bg: {{ $bandColor }}" role="group" aria-label="Datos de la lección">
    <div class="mx-auto grid w-fit max-w-full grid-cols-1 items-center gap-y-2 px-3 py-3 text-left font-sans text-xs/4 uppercase sm:flex sm:min-h-17.75 sm:flex-wrap sm:items-stretch sm:justify-center sm:divide-x sm:divide-current sm:border-x sm:border-current sm:p-0">
      @if (!empty($metadata['series_name']))
        <div class="lesson-metadata__item lesson-metadata__series flex min-w-0 items-center px-3 sm:items-start sm:px-4 sm:pt-4 sm:pb-2">
          <span class="lesson-metadata__series-name max-w-56 wrap-anywhere">{{ $metadata['series_name'] }}</span>
        </div>
      @endif
      @if ($family)
        @include('partials.lesson-family-badge', ['family' => $family])
      @endif
      @if (!empty($metadata['production']))
        <div class="lesson-metadata__item lesson-metadata__production flex min-w-0 items-center gap-2 px-3 sm:items-start sm:px-4 sm:pt-4 sm:pb-2" data-lesson-production="{{ $metadata['production']['value'] }}">
          <x-icon :name="$metadata['production']['icon']" class="lesson-metadata__production-icon size-6 shrink-0 sm:-mt-1" aria-hidden="true" focusable="false" />
          <span class="lesson-metadata__production-label">{{ $metadata['production']['label'] }}</span>
        </div>
      @endif
      @if (is_user_logged_in())
        <div class="lesson-metadata__item flex items-center px-3 sm:items-start sm:px-4">
          <button type="button" id="mark-complete" data-lesson-completion data-post-id="{{ get_the_ID() }}"
            data-completed="{{ $is_completed ? 'true' : 'false' }}" aria-pressed="{{ $is_completed ? 'true' : 'false' }}"
            aria-label="{{ $is_completed ? 'Marcar como no vista' : 'Marcar como vista' }}"
            class="{{ $is_completed ? 'completed' : '' }} flex min-h-11 cursor-pointer items-center gap-2 sm:items-start sm:pt-4 sm:[&>svg]:-mt-1 text-left text-inherit! uppercase hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current disabled:cursor-wait">
            <x-coolicon-show aria-hidden="true" class="icon-show {{ $is_completed ? '' : 'hidden' }} size-6 shrink-0" />
            <x-coolicon-hide aria-hidden="true" class="icon-hide {{ $is_completed ? 'hidden' : '' }} size-6 shrink-0" />
            <span class="btn-text">{{ $is_completed ? 'Vista' : 'Marcar como vista' }}</span>
          </button>
        </div>
      @endif
    </div>
  </div>
@endif
