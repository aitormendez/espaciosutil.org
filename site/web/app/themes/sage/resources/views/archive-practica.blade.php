@extends('layouts.app')

@section('content')
  <section class="bg-morado5/90 text-blanco px-4 py-12 md:px-8">
    <div class="mx-auto max-w-4xl">
      <h1 class="font-display text-4xl">Prácticas</h1>
      @if (\EspacioSutil\Practicas\Catalog::allowed() || \EspacioSutil\Practicas\Catalog::editor())
        <p class="mt-4">Consulta las prácticas del curso y las lecciones donde se enseñan.</p>
        <div class="mt-8 grid gap-6 md:grid-cols-2">
          @while(have_posts()) @php the_post(); @endphp
            @if (get_post()->post_password === '')
              <article class="rounded border border-white/20 p-6">
                <h2 class="font-display text-2xl"><a class="underline" href="{{ get_permalink() }}">{{ get_the_title() }}</a></h2>
                <div class="mt-3">{!! get_the_excerpt() !!}</div>
              </article>
            @endif
          @endwhile
        </div>
        @if (empty($GLOBALS['wp_query']->posts))
          <p class="mt-8">Todavía no hay prácticas publicadas.</p>
        @endif
        <nav class="mt-8" aria-label="Páginas de prácticas">{!! get_the_posts_pagination() !!}</nav>
      @else
        @include('partials.practicas-access')
      @endif
    </div>
  </section>
@endsection
