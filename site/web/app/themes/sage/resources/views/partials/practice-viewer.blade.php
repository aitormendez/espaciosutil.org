@php
  $guide = \EspacioSutil\Practicas\Guide::forPost($practice);
  $viewerId = wp_unique_id('practice-');
  $reading = \EspacioSutil\Practicas\Guide::readingHtml(wpautop(wp_kses_post($practice->post_content)), $viewerId, $guide['sections']);
@endphp
<section class="practice-viewer {{ !empty($embedded) ? 'practice-viewer--embedded' : '' }}" data-practice-viewer data-mode="reading" aria-label="{{ get_the_title($practice) }}" data-guide='@json($guide, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)'>
  <header class="practice-fullscreen-title"><span>{{ get_the_title($practice) }}</span></header>
  <nav class="practice-index" id="{{ $viewerId }}-index" aria-label="Índice de la práctica" hidden>
    <div class="practice-index-content">
    @if (!empty($embedded))
      <button type="button" class="practice-index-close" data-action="index">Cerrar índice</button>
    @endif
    <p class="practice-eyebrow">En esta práctica</p>
    @foreach ($guide['sections'] as $section)
      <button type="button" data-practice-jump="{{ $section['screen'] }}" data-practice-section="{{ $loop->index }}">{{ $section['title'] }}</button>
    @endforeach
    </div>
  </nav>
  <div class="practice-viewer-main">
    <div class="practice-toolbar" hidden data-practice-toolbar>
      <button type="button" class="practice-button practice-index-toggle" data-action="index" aria-expanded="false" aria-controls="{{ $viewerId }}-index">@include('partials.practice-icon', ['icon'=>'index']) <span>Mostrar índice</span></button>
      <div class="practice-modes" role="group" aria-label="Modo de lectura">
        <button type="button" data-action="guide" aria-pressed="true">Guía paso a paso</button>
        <button type="button" data-action="reading" aria-pressed="false">Texto completo</button>
      </div>
      <div class="practice-tools">
        <button type="button" class="practice-button" data-action="audio" aria-pressed="false"><span data-audio-off>@include('partials.practice-icon', ['icon'=>'volume-off'])</span><span data-audio-on hidden>@include('partials.practice-icon', ['icon'=>'volume'])</span><span data-audio-label>Activar audio</span></button>
        <button type="button" class="practice-button" data-action="fullscreen"><span data-fullscreen-enter>@include('partials.practice-icon', ['icon'=>'maximize'])</span><span data-fullscreen-exit hidden>@include('partials.practice-icon', ['icon'=>'minimize'])</span><span data-fullscreen-label>Pantalla completa</span></button>
      </div>
    </div>
    <div class="practice-screen" data-practice-screen tabindex="-1" hidden></div>
    <div class="practice-reading" data-practice-reading tabindex="-1">{!! $reading !!}</div>
    <footer class="practice-controls" hidden data-practice-controls>
      <progress data-practice-progress max="{{ max(1, count($guide['screens'])) }}" value="1" aria-label="Progreso de la práctica"></progress>
      <button type="button" class="practice-button" data-action="previous" disabled>@include('partials.practice-icon', ['icon'=>'left']) Anterior</button>
      <span class="practice-counter" aria-live="polite" aria-atomic="true" data-practice-counter></span>
      <button type="button" class="practice-button practice-button--primary" data-action="next">Siguiente @include('partials.practice-icon', ['icon'=>'right'])</button>
    </footer>
    <p class="practice-status" role="status" data-practice-status></p>
  </div>
</section>
