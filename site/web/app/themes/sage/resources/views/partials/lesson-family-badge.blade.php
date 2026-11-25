<div class="lesson-metadata__item lesson-family-badge flex min-w-0 items-center gap-2 px-3 sm:items-start sm:px-4 sm:pt-4 sm:pb-2" data-lesson-family="{{ $family['slug'] }}">
  @include('partials.cde-family-icon', ['family' => $family, 'iconClass' => 'lesson-family-badge__icon block size-12 shrink-0 sm:-mt-2'])
  <span class="lesson-family-badge__label max-w-32 min-w-0 wrap-anywhere whitespace-normal">{{ $family['label'] }}</span>
</div>
