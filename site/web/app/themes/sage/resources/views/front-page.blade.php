@php
  $cde_free_lesson_url = home_url('/lecciones-del-cde/seth/quien-es-seth/');
  $cde_membership_url = get_permalink(2242) ?: home_url('/suscripcion/');
  $cde_index_url = get_permalink(2648) ?: home_url('/indice-de-lecciones/');
  $cde_program_url = get_permalink(2288) ?: home_url('/el-curso-en-profundidad/');
  $cde_support_items = [
      [
          'text' => 'Lectura comentada',
          'icon' => 'tabler-book-filled',
      ],
      [
          'text' => 'Clarificación y contexto',
          'icon' => 'tabler-sparkles-filled',
      ],
      [
          'text' => 'Biblioteca en expansión',
          'icon' => 'tabler-bookmark-filled',
      ],
  ];
@endphp

@extends('layouts.app')

@section('content')
  <div id="cosmos" class="hidden xl:block"></div>
  <div class="loading-label absolute left-0 top-0 hidden h-screen w-full items-center justify-center p-6 xl:flex">
    <div
      class="bg-negro/80 border-blanco relative top-32 mx-auto rounded border p-6 text-center text-2xl font-thin md:top-40 md:text-3xl">
      Construyendo Sistema Sutil
    </div>
  </div>

  <div id="solapa"
    class="solapa bg-negro/80 border-blanco/50 absolute left-0 top-40 z-[9999999] ml-12 border p-10 opacity-0 transition-opacity duration-1000">
    <div id="epig" class="text-gris2"></div>
    <div id="nomb" class="text-3xl"></div>
  </div>

  <section class="w-full border-t bg-black/80 px-6 py-12 font-sans md:px-0 md:py-16">
    <div class="mx-auto max-w-4xl">
      <article class="flex flex-col">
        <header class="flex flex-col items-center">
          <div class="text-cde-light">
            <x-es-cde-arbol-peq class="h-20 w-20 md:h-24 md:w-24" />
          </div>
          <p class="text-cde-light mt-4 text-sm font-semibold uppercase tracking-[0.24em]">
            Curso de desarrollo espiritual
          </p>

          <div class="mt-8 flex flex-col items-center text-center">
            <h2 class="text-gris1 max-w-4xl text-balance text-4xl font-light md:text-5xl lg:text-[3.4rem]">
              Accede a la complejidad de los textos revelados con el <span class="text-cde-light-2 font-[500]">Curso de
                desarrollo espiritual</span>.
            </h2>
            <p class="text-gris2 mt-5 max-w-3xl text-lg font-light leading-relaxed md:text-xl">
              Una propuesta de estudio basada en lectura comentada, clarificación de conceptos y navegación estructurada.
            </p>
          </div>
        </header>

        <div class="mt-8 flex flex-col gap-4 lg:flex-row lg:flex-nowrap">
          <x-cta href="{{ $cde_free_lesson_url }}" text="Ver lección gratuita"
            clases="bg-cde hover:text-morado5 !min-w-0 w-full font-semibold text-gray-200 lg:flex-1"
            icon="tabler-eye-filled" />
          <x-cta href="{{ $cde_program_url }}" text="Ver el curso en profundidad"
            clases="bg-cde hover:text-morado5 !min-w-0 w-full font-semibold text-gray-200 lg:flex-1"
            icon="tabler-arrow-badge-right-filled" />
          <x-cta href="{{ $cde_membership_url }}" text="Suscripción"
            clases="bg-cde/50 hover:text-morado5 !min-w-0 w-full font-semibold text-gray-200 lg:flex-1"
            icon="tabler-key-filled" />
        </div>

        <div class="mt-5 flex flex-col items-start gap-3">
          <a href="{{ $cde_index_url }}"
            class="text-gris1 hover:text-cde inline-flex items-center gap-2 text-lg font-light underline decoration-white/30 underline-offset-4 transition-colors">
            Explorar series y lecciones
            <span aria-hidden="true">→</span>
          </a>
        </div>

        <div class="mt-10 flex justify-center">
          <x-list-card :items="$cde_support_items" class="text-gris1 mx-auto w-fit max-w-3xl items-start"
            item-class="flex items-center gap-3 self-start text-left text-lg font-thin leading-snug"
            icon-class="text-cde-light h-[48px] w-[48px] shrink-0 rounded-full border border-white p-2" />
        </div>
      </article>
    </div>
  </section>

  <section class="w-full border-t px-6 py-12 md:px-0">
    <div class="page-content prose prose-xl md:prose-2xl mx-auto max-w-4xl !leading-tight">
      @php(the_content())</div>
  </section>


  <section class="mb-12 w-full border-t px-6">
    <h2 class="py-6 text-center font-sans">Últimas noticias publicadas</h2>
    <div class="noticias flex w-full flex-wrap justify-center">
      <?php
            $query = new WP_Query([
                'post_type' => 'noticia',
                'posts_per_page' => 4,
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
            if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
            ?>
      <div class="noticia w-full sm:w-1/2 md:w-1/3 lg:w-1/4">
        <a href="{{ get_permalink() }}" class="hover:text-morado3 mb-4 block">
          <h2 class="entry-title text-2xl">{{ get_the_title() }}</h2>
        </a>
        {!! get_the_post_thumbnail(null, 'large') !!}
      </div>
      <?php endwhile; endif; wp_reset_postdata(); ?>
    </div>
  </section>

  <section class="mb-12 border-t px-6">
    <h2 class="py-6 text-center font-sans">Últimos vídeos publicados</h2>
    <div id="ultimos-videos" class="flex flex-wrap"></div>
  </section>
@endsection
