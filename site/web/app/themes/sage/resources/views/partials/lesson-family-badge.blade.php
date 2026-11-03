<div class="lesson-metadata__item lesson-metadata__series lesson-family-badge" data-lesson-family="{{ $family['slug'] }}">
  <x-icon :name="$family['icon']" class="lesson-family-badge__icon" aria-hidden="true" focusable="false" />
  <div class="lesson-metadata__series-copy">
    @if (!empty($series_name))
      <span class="lesson-metadata__series-name">{{ $series_name }}</span>
    @endif
    <span class="lesson-family-badge__label">{{ $family['label'] }}</span>
  </div>
</div>
