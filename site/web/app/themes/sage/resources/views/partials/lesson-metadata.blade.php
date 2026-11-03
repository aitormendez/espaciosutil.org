@if (!empty($metadata['family']) || !empty($metadata['series_name']) || !empty($metadata['production']))
  <div class="lesson-metadata" role="group" aria-label="Datos de la lección">
    @if (!empty($metadata['family']))
      @include('partials.lesson-family-badge', [
          'family' => $metadata['family'],
          'series_name' => $metadata['series_name'] ?? null,
      ])
    @elseif (!empty($metadata['series_name']))
      <div class="lesson-metadata__item lesson-metadata__series">
        <span class="lesson-metadata__series-name">{{ $metadata['series_name'] }}</span>
      </div>
    @endif

    @if (!empty($metadata['production']))
      <div class="lesson-metadata__item lesson-metadata__production"
        data-lesson-production="{{ $metadata['production']['value'] }}">
        <x-icon :name="$metadata['production']['icon']" class="lesson-metadata__production-icon" aria-hidden="true" focusable="false" />
        <span class="lesson-metadata__production-label">{{ $metadata['production']['label'] }}</span>
      </div>
    @endif
  </div>
@endif
