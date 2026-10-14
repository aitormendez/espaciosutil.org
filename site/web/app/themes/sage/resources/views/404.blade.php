@extends('layouts.app')

@section('content')
  <section aria-labelledby="not-found-title"
    class="bg-negro/60 flex min-h-[560px] items-center px-6 py-16 sm:min-h-[640px] sm:px-8 sm:py-24">
    <div class="mx-auto w-full max-w-2xl text-center">
      <p class="text-morado2 mb-6 font-sans text-sm tracking-[0.2em] uppercase">
        {{ __('Error 404', 'sage') }}
      </p>

      <h1 id="not-found-title" class="text-blanco text-4xl leading-tight font-light text-balance sm:text-6xl">
        {{ __('Esta página no está aquí', 'sage') }}
      </h1>

      <p class="text-gris1 mx-auto mt-6 max-w-lg font-sans text-base leading-relaxed font-light sm:text-lg">
        {{ __('Puede que el enlace haya cambiado o que la página ya no exista. Puedes volver al inicio o buscar el contenido que te interesa.', 'sage') }}
      </p>

      <a href="{{ home_url('/') }}"
        @if (should_prevent_barba_for_url(home_url('/'))) data-barba-prevent @endif
        class="btn btn--morado3 mt-8 min-h-12 px-6 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-morado2 motion-reduce:transition-none">
        {{ __('Volver al inicio', 'sage') }}
      </a>

      <form role="search" method="get" action="{{ home_url('/') }}"
        class="mx-auto mt-12 max-w-lg border-t border-white/20 pt-8 text-left font-sans">
        <label for="not-found-search" class="text-gris1 mb-3 block text-sm">
          {{ __('Buscar en Espacio Sutil', 'sage') }}
        </label>
        <div class="flex flex-col gap-3 sm:flex-row">
          <input id="not-found-search" type="search" name="s" value="{{ get_search_query() }}"
            placeholder="{{ __('¿Qué te gustaría encontrar?', 'sage') }}" required
            class="bg-negro/80 text-blanco placeholder:text-gris2 min-h-12 w-full min-w-0 rounded-sm border border-gris2 px-4 py-3 text-base focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-morado2">
          <button type="submit"
            class="btn btn--secondary min-h-12 shrink-0 px-6 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-morado2 motion-reduce:transition-none">
            {{ __('Buscar', 'sage') }}
          </button>
        </div>
      </form>
    </div>
  </section>
@endsection
