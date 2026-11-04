@extends('layouts.app')

@section('content')
  <section aria-labelledby="search-results-title" class="bg-negro border-b border-white/15 px-6 py-16 sm:px-8 sm:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-5xl">
      <p class="text-morado2 font-sans text-sm tracking-[0.16em] uppercase">
        {{ __('Búsqueda', 'sage') }}
      </p>

      <h1 id="search-results-title" class="text-blanco mt-5 max-w-4xl text-4xl leading-tight font-light text-balance sm:text-5xl lg:text-6xl">
        @if ($search_query !== '')
          {{ __('Resultados para', 'sage') }} <span class="text-morado1">“{{ $search_query }}”</span>
        @else
          {{ __('Buscar en Espacio Sutil', 'sage') }}
        @endif
      </h1>

      @if ($search_query !== '')
        <p class="text-gris1 mt-5 font-sans text-base font-light sm:text-lg" aria-live="polite">
          {{ sprintf(_n('%s resultado encontrado', '%s resultados encontrados', $search_results_count, 'sage'), number_format_i18n($search_results_count)) }}
        </p>
      @endif

      <div class="mt-10 max-w-3xl">
        @include('forms.search', ['search_input_id' => 'results-search'])
      </div>
    </div>
  </section>

  <section aria-label="{{ __('Resultados de búsqueda', 'sage') }}" class="bg-negro px-6 py-12 sm:px-8 sm:py-16 lg:py-20">
    <div class="mx-auto w-full max-w-5xl">
      @if (have_posts())
        <div class="border-gris3 hidden border-b pb-3 font-sans text-xs tracking-[0.14em] text-gris2 uppercase sm:grid sm:grid-cols-[10rem_minmax(0,1fr)_2rem] sm:gap-8">
          <span>{{ __('Procedencia · Tipo', 'sage') }}</span>
          <span>{{ __('Contenido', 'sage') }}</span>
          <span class="sr-only">{{ __('Abrir', 'sage') }}</span>
        </div>

        <ol class="divide-gris3 divide-y border-b border-gris3">
          @while(have_posts()) @php the_post(); @endphp
            <li>
              @include('partials.content-search')
            </li>
          @endwhile
        </ol>

        <nav aria-label="{{ __('Páginas de resultados', 'sage') }}"
          class="mt-12 font-sans [&_.nav-links]:flex [&_.nav-links]:flex-wrap [&_.nav-links]:items-center [&_.nav-links]:gap-2 [&_.page-numbers]:inline-flex [&_.page-numbers]:min-h-11 [&_.page-numbers]:min-w-11 [&_.page-numbers]:items-center [&_.page-numbers]:justify-center [&_.page-numbers]:rounded-sm [&_.page-numbers]:border [&_.page-numbers]:border-gris3 [&_.page-numbers]:px-3 [&_.page-numbers]:text-gris1 [&_.page-numbers]:transition-colors [&_.page-numbers:hover]:border-morado2 [&_.page-numbers:hover]:text-blanco [&_.page-numbers:focus-visible]:outline-2 [&_.page-numbers:focus-visible]:outline-offset-2 [&_.page-numbers:focus-visible]:outline-morado2 [&_.current]:border-morado2 [&_.current]:bg-morado3 [&_.current]:text-blanco motion-reduce:[&_.page-numbers]:transition-none">
          {!! get_the_posts_pagination([
              'mid_size' => 1,
              'prev_text' => __('Anterior', 'sage'),
              'next_text' => __('Siguiente', 'sage'),
          ]) !!}
        </nav>
      @else
        <div class="border-gris3 max-w-3xl border-y py-12 sm:py-16">
          <h2 class="text-blanco text-3xl font-light sm:text-4xl">
            {{ __('No hemos encontrado coincidencias', 'sage') }}
          </h2>
          <p class="text-gris1 mt-5 max-w-2xl font-sans text-base leading-relaxed font-light sm:text-lg">
            {{ __('Prueba con menos palabras, revisa la ortografía o utiliza un término más general.', 'sage') }}
          </p>
          <a href="{{ home_url('/') }}"
            @if (should_prevent_barba_for_url(home_url('/'))) data-barba-prevent @endif
            class="text-morado1 mt-8 inline-flex min-h-11 items-center border-b border-morado2 font-sans text-base hover:text-blanco focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-morado2">
            {{ __('Volver al inicio', 'sage') }}
          </a>
        </div>
      @endif
    </div>
  </section>
@endsection
