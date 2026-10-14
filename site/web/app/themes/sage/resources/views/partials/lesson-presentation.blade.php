@if (!empty($lesson_presentation))
  <section class="lesson-presentation not-prose" aria-label="Presentación de la lección"
    data-lesson-presentation='@json($lesson_presentation, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)'>
    <header class="lesson-presentation-heading">
      <h2>Presentación de la lección</h2>
      <p>Recorre las diapositivas a tu ritmo. Amplía para ver los detalles.</p>
    </header>
    <div data-presentation-home>
      <div data-presentation-shell class="lesson-presentation-shell">
        <div class="swiper" tabindex="0" aria-label="Diapositivas" aria-busy="true">
          <div class="swiper-wrapper"></div>
          <div class="lesson-presentation-loading" data-presentation-loading role="status" aria-live="polite">
            <span class="lesson-presentation-loading-label">
              <x-dynamic-component component="tabler-loader-2" aria-hidden="true" />
              <span>Cargando diapositiva…</span>
            </span>
          </div>
        </div>
        <div class="lesson-presentation-toolbar">
          <div class="lesson-presentation-navigation">
            <button type="button" data-presentation-prev aria-label="Diapositiva anterior" aria-keyshortcuts="ArrowLeft" title="Anterior (←)" disabled>
              <x-dynamic-component component="tabler-chevron-left" aria-hidden="true" />
            </button>
            <span data-presentation-counter aria-live="polite" aria-atomic="true">— / —</span>
            <button type="button" data-presentation-next aria-label="Diapositiva siguiente" aria-keyshortcuts="ArrowRight" title="Siguiente (→)" disabled>
              <x-dynamic-component component="tabler-chevron-right" aria-hidden="true" />
            </button>
          </div>
          <div class="lesson-presentation-actions">
            <button type="button" data-presentation-zoom aria-label="Acercar" title="Acercar" disabled>
              <span data-presentation-zoom-in><x-dynamic-component component="tabler-zoom-in" aria-hidden="true" /></span>
              <span data-presentation-zoom-out hidden><x-dynamic-component component="tabler-zoom-out" aria-hidden="true" /></span>
            </button>
            <button type="button" data-presentation-expand aria-label="Ampliar" title="Ampliar" disabled>
              <x-dynamic-component component="tabler-arrows-maximize" aria-hidden="true" />
            </button>
            <button type="button" data-presentation-close aria-label="Cerrar" title="Cerrar" hidden>
              <x-dynamic-component component="tabler-x" aria-hidden="true" />
            </button>
          </div>
        </div>
        <p data-presentation-status role="status"></p>
        <button type="button" data-presentation-retry hidden>
          <x-dynamic-component component="tabler-refresh" aria-hidden="true" />
          <span>Volver a intentar</span>
        </button>
      </div>
    </div>
    <dialog aria-label="Presentación ampliada"><div data-presentation-dialog-content></div></dialog>
    <noscript>Activa JavaScript para recorrer esta presentación.</noscript>
  </section>
@endif
