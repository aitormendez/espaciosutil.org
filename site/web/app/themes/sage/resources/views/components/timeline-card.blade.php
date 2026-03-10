@props([
    'title' => '',
    'status' => '',
    'statusLabel' => '',
    'paragraphs' => [],
    'first' => false,
    'last' => false,
])

@php
  $isSingle = $first && $last;

  $statusIcon = match ($status) {
      'available' => 'tabler-circle-check-filled',
      'in-progress' => 'tabler-progress-check',
      default => 'tabler-circle-x-filled',
  };
@endphp

<article
  {{ $attributes->class([
      'timeline-card relative',
      'timeline-card--top' => $first && !$last,
      'timeline-card--middle' => !$first && !$last,
      'timeline-card--bottom' => $last && !$first,
      'timeline-card--single' => $isSingle,
      'timeline-card--available bg-negro/80' => $status === 'available',
      'timeline-card--in-progress' => $status === 'in-progress',
      'border-gray-500 bg-white/5 text-white/20' => $status !== 'available',
  ]) }}>
  <div class="timeline-card__status absolute -left-[28px] top-8 mb-6 flex items-center gap-4 lg:-left-[55px]">
    <div
      {{ $attributes->class([
          'rounded-full p-2 lg:p-4',
          'bg-cde' => $status === 'available',
          'bg-gray-800 text-gray-500' => $status !== 'available',
      ]) }}>
      <x-dynamic-component :component="$statusIcon" @class([
          'timeline-card__status-icon',
          'w-10 h-10 lg:w-20 lg:h-20',
          'timeline-card__status-icon--available' => $status === 'available',
          'timeline-card__status-icon--in-progress' => $status === 'in-progress',
      ]) />
    </div>
    <span class="font-sans text-xl font-light uppercase tracking-widest">{{ $statusLabel }}</span>
  </div>

  <div class="timeline-card__content">
    <h3
      {{ $attributes->class([
          'timeline-card__content font-sans text-4xl mb-6',
          'text-white/80' => $status === 'available',
          'text-white/20' => $status !== 'available',
      ]) }}>
      {{ $title }}</h3>

    <div
      {{ $attributes->class([
          'prose-cde',
          'text-white/80' => $status === 'available',
          'text-white/20' => $status !== 'available',
      ]) }}">
      @foreach ($paragraphs as $paragraph)
        <p>{{ $paragraph }}</p>
      @endforeach
    </div>
  </div>
</article>
