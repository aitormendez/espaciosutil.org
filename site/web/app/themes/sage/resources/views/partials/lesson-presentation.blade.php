@if (!empty($lesson_presentation))
  @php
    $presentationButtonClasses = 'inline-flex min-h-11 min-w-11 cursor-pointer items-center justify-center gap-1.5 rounded border border-white/25 bg-transparent px-3 py-1.5 text-sm text-inherit hover:enabled:bg-white/10 disabled:cursor-default disabled:opacity-40 focus-visible:outline-2 focus-visible:outline-sol focus-visible:-outline-offset-3 [&_svg]:size-5 [&_svg]:shrink-0';
  @endphp
  <section class="lesson-presentation not-prose mx-auto my-10 w-[calc(100%-2rem)] max-w-4xl font-sans text-purple-50 md:w-[calc(100%-3rem)] [&_[hidden]]:hidden!" aria-label="Presentación de la lección"
    data-lesson-presentation='@json($lesson_presentation, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)'>
    <header class="lesson-presentation-heading">
      <h2 class="font-display mb-1.5 text-[clamp(1.5rem,3vw,2rem)]">Presentación de la lección</h2>
      <p class="mb-5 text-[0.95rem] opacity-80">Recorre las diapositivas a tu ritmo. Amplía para ver los detalles.</p>
    </header>
    <div data-presentation-home>
      <div data-presentation-shell class="lesson-presentation-shell overflow-hidden rounded border border-white/20 bg-transparent">
        <div class="swiper w-full aspect-video bg-transparent focus-visible:outline-2 focus-visible:outline-sol focus-visible:-outline-offset-3" tabindex="0" aria-label="Diapositivas" aria-busy="true">
          <div class="swiper-wrapper"></div>
          <div class="lesson-presentation-loading pointer-events-none absolute inset-0 z-2 grid place-items-center" data-presentation-loading role="status" aria-live="polite">
            <span class="inline-flex items-center gap-2.5 rounded border border-white/10 bg-morado5/95 px-4 py-3 text-sm text-purple-50">
              <x-dynamic-component component="tabler-loader-2" class="size-5 shrink-0 animate-spin motion-reduce:animate-none" aria-hidden="true" />
              <span>Cargando diapositiva…</span>
            </span>
          </div>
        </div>
        <div class="lesson-presentation-toolbar flex items-center justify-between gap-2 p-3 max-[420px]:flex-wrap max-[420px]:p-2">
          <div class="flex items-center gap-1.5">
            <button type="button" class="{{ $presentationButtonClasses }}" data-presentation-prev aria-label="Diapositiva anterior" aria-keyshortcuts="ArrowLeft" title="Anterior (←)" disabled>
              <x-dynamic-component component="tabler-chevron-left" aria-hidden="true" />
            </button>
            <span class="min-w-16 text-center text-sm tabular-nums" data-presentation-counter aria-live="polite" aria-atomic="true">— / —</span>
            <button type="button" class="{{ $presentationButtonClasses }}" data-presentation-next aria-label="Diapositiva siguiente" aria-keyshortcuts="ArrowRight" title="Siguiente (→)" disabled>
              <x-dynamic-component component="tabler-chevron-right" aria-hidden="true" />
            </button>
          </div>
          <div class="flex items-center gap-1.5 max-[420px]:ml-auto">
            <button type="button" class="{{ $presentationButtonClasses }} max-[420px]:disabled:hidden" data-presentation-zoom aria-label="Acercar" title="Acercar" disabled>
              <span data-presentation-zoom-in><x-dynamic-component component="tabler-zoom-in" aria-hidden="true" /></span>
              <span data-presentation-zoom-out hidden><x-dynamic-component component="tabler-zoom-out" aria-hidden="true" /></span>
            </button>
            <button type="button" class="{{ $presentationButtonClasses }}" data-presentation-expand aria-label="Ampliar" title="Ampliar" disabled>
              <x-dynamic-component component="tabler-arrows-maximize" aria-hidden="true" />
            </button>
            <button type="button" class="{{ $presentationButtonClasses }}" data-presentation-close aria-label="Cerrar" title="Cerrar" hidden>
              <x-dynamic-component component="tabler-x" aria-hidden="true" />
            </button>
          </div>
        </div>
        <p class="px-4 py-2 empty:hidden" data-presentation-status role="status"></p>
        <button type="button" class="{{ $presentationButtonClasses }} mx-4 mt-2 mb-4" data-presentation-retry hidden>
          <x-dynamic-component component="tabler-refresh" aria-hidden="true" />
          <span>Volver a intentar</span>
        </button>
      </div>
    </div>
    <dialog class="m-0 h-dvh max-h-none w-screen max-w-none border-0 bg-morado5 p-0 text-purple-50 backdrop:bg-morado5/95 [&_.lesson-presentation-shell]:flex [&_.lesson-presentation-shell]:h-dvh [&_.lesson-presentation-shell]:flex-col [&_.lesson-presentation-shell]:border-0 [&_.lesson-presentation-shell]:pb-[env(safe-area-inset-bottom)] [&_.swiper]:min-h-0 [&_.swiper]:flex-1 [&_.swiper]:aspect-auto" aria-label="Presentación ampliada"><div class="h-full" data-presentation-dialog-content></div></dialog>
    <noscript>Activa JavaScript para recorrer esta presentación.</noscript>
  </section>
@endif
