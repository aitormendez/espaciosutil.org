@props([
    'question' => '',
    'answer' => '',
    'open' => false,
])

@php
  $question = trim((string) $question);
  $answer = trim((string) $answer);

  if ($answer !== '' && function_exists('wpautop')) {
      $answer = wpautop($answer);
  }
@endphp

@if ($question !== '' && $answer !== '')
  <details class="rounded-xs border-t border-white/60 last:border-b" @if ($open) open @endif>
    <summary class="cursor-pointer select-none py-3 text-lg font-light text-white/90">
      {{ $question }}
    </summary>
    <div class="text-gris2 border-t border-white/15 px-4 py-3 text-base font-light">
      {!! wp_kses_post($answer) !!}
    </div>
  </details>
@endif
