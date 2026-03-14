@php
  $legalUrls = legal_page_urls();
@endphp

<div id="cookie-consent-root" class="pointer-events-none fixed inset-x-0 bottom-0 z-[120] hidden px-4 py-4"
  data-cookie-consent-root>
  <div
    class="cookie-consent-banner bg-negro/95 text-gris1 pointer-events-auto mx-auto flex max-w-md flex-col gap-4 rounded border border-white/15 px-5 py-5 font-sans text-sm shadow-2xl shadow-black/50"
    data-cookie-banner hidden>
    <div class="max-w-3xl space-y-3">
      <p class="text-xs uppercase tracking-[0.18em] text-white/55">Cookies</p>
      <p class="text-gris1 text-base font-light leading-snug">
        Usamos cookies técnicas necesarias para el funcionamiento del sitio y, si lo aceptas, cookies analíticas de
        Matomo para medir uso y mejorar la experiencia.
      </p>
    </div>

    <div class="max-w-3xl">
      <p class="text-sm text-white/40">
        Puedes aceptar, rechazar o configurar la analítica. Más información en
        <a href="{{ esc_url($legalUrls['cookies']) }}" class="hover:text-blanco underline underline-offset-4">Política
          de cookies</a>
        y
        <a href="{{ esc_url($legalUrls['privacy']) }}" class="hover:text-blanco underline underline-offset-4">Política
          de
          privacidad</a>.
      </p>
    </div>

    <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
      <button type="button" class="cookie-consent-button btn" data-cookie-reject>
        Rechazar
      </button>
      <button type="button" class="cookie-consent-button btn" data-cookie-open>
        Configurar
      </button>
      <button type="button" class="cookie-consent-button btn btn--morado3" data-cookie-accept>
        Aceptar
      </button>
    </div>
  </div>

  <div class="cookie-consent-panel pointer-events-auto fixed inset-0 z-[130] hidden px-4 py-8" data-cookie-panel hidden
    aria-hidden="true">
    <button type="button" class="absolute inset-0 h-full w-full bg-black/70" data-cookie-close
      aria-label="Cerrar panel de cookies"></button>

    <div
      class="cookie-consent-card bg-negro/96 text-gris1 relative mx-auto flex max-h-[calc(100vh-4rem)] w-full max-w-3xl flex-col overflow-hidden rounded border border-white/15 font-sans shadow-2xl shadow-black/60">
      <div class="border-b border-white/10 px-6 py-5">
        <p class="text-xs uppercase tracking-[0.18em] text-white/50">Preferencias</p>
        <h2 class="text-gris1 mt-2 text-2xl font-light">Configurar cookies</h2>
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/70">
          Las cookies técnicas son necesarias y no pueden desactivarse desde aquí. La analítica es opcional y solo se
          activará si la aceptas.
        </p>
      </div>

      <div class="flex-1 space-y-6 overflow-y-auto px-6 py-6">
        <section class="rounded border border-white/10 bg-white/5 p-5">
          <div class="flex justify-between gap-4">
            <div class="space-y-2">
              <h3 class="text-gris1 text-lg font-medium">Cookies técnicas</h3>
              <p class="text-sm text-white/40">
                Necesarias para acceso, navegación, seguridad, área privada, membresía y gestión del consentimiento.
              </p>
            </div>
            <div
              class="inline-flex items-center justify-center rounded border border-white/15 px-3 py-1 text-xs uppercase tracking-[0.14em] text-white/60">
              <span class="text-center">
                Siempre activas
              </span>
            </div>
          </div>
        </section>

        <section class="rounded border border-white/10 bg-white/5 p-5">
          <div class="flex items-start justify-between gap-4">
            <div class="space-y-2">
              <h3 class="text-gris1 text-lg font-medium">Analítica</h3>
              <p class="text-sm text-white/40">
                Matomo nos permite medir páginas visitadas, procedencia del tráfico y uso general del sitio para mejorar
                contenidos y experiencia.
              </p>
            </div>
            <label class="cookie-consent-toggle" for="cookie-analytics">
              <input id="cookie-analytics" type="checkbox" data-cookie-analytics>
              <span aria-hidden="true"></span>
              <span class="sr-only">Activar analítica</span>
            </label>
          </div>
        </section>

        <p class="text-sm leading-relaxed text-white/65">
          Puedes ampliar información en
          <a href="{{ esc_url($legalUrls['cookies']) }}" class="hover:text-blanco text-morado3">Política
            de cookies</a>
          y
          <a href="{{ esc_url($legalUrls['privacy']) }}" class="hover:text-blanco text-morado3">Política
            de privacidad</a>.
        </p>
      </div>

      <div class="flex flex-col gap-3 border-t border-white/10 px-6 py-5 sm:flex-row sm:justify-between">
        <button type="button" class="cookie-consent-button btn" data-cookie-reject>
          Rechazar todo lo opcional
        </button>

        <div class="flex flex-col gap-3 sm:flex-row">
          <button type="button" class="cookie-consent-button btn" data-cookie-close>
            Cerrar
          </button>
          <button type="button" class="cookie-consent-button btn btn--morado3" data-cookie-save>
            Guardar preferencias
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
