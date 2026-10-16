@extends('layouts.app')
@section('content')
  <section class="practice-page">
    <div class="practice-container">
      <p class="practice-eyebrow">Curso de Desarrollo Espiritual</p>
      <h1>Prácticas</h1>
      @if (\EspacioSutil\Practicas\Catalog::allowed() || \EspacioSutil\Practicas\Catalog::editor())
        <form method="get" action="{{ get_post_type_archive_link('practica') }}" class="practice-search" role="search" aria-label="Buscar prácticas">
          <label>Buscar prácticas<input name="practice_search" type="search" value="{{ \EspacioSutil\Practicas\Catalog::search() }}" placeholder="Buscar por título o palabra clave"></label>
          <label>Serie de las lecciones<select name="practice_series"><option value="0">Todas las series</option>
            @foreach (\EspacioSutil\Practicas\Catalog::series() as $series)
              <option value="{{ $series->term_id }}" @selected(\EspacioSutil\Practicas\Catalog::selectedSeries() === $series->term_id)>{{ $series->name }}</option>
            @endforeach
          </select></label>
          <button class="practice-button" type="submit">Buscar</button>
        </form>
        <p class="practice-count">{{ $GLOBALS['wp_query']->found_posts }} {{ $GLOBALS['wp_query']->found_posts === 1 ? 'práctica' : 'prácticas' }}</p>
        <div class="practice-list">
          @while(have_posts()) @php the_post(); $lessons = \EspacioSutil\Practicas\Catalog::lessonsFor(get_the_ID()); @endphp
            <article class="practice-card">
              <div><h2><a href="{{ get_permalink() }}">{{ get_the_title() }}</a></h2><p>{{ get_the_excerpt() }}</p>
                <div class="practice-lesson-links">
                  @foreach ($lessons as $lesson)
                    <a class="practice-lesson-link" href="{{ get_permalink($lesson) }}">{{ get_the_title($lesson) }}</a>
                  @endforeach
                </div>
              </div>
              <a class="practice-button practice-button--primary" href="{{ get_permalink() }}">Abrir práctica @include('partials.practice-icon', ['icon'=>'right'])</a>
            </article>
          @endwhile
        </div>
        @if (!have_posts() && !$GLOBALS['wp_query']->found_posts)
          <p>No hay prácticas que coincidan con esta búsqueda.</p>
        @endif
        <nav class="practice-pagination" aria-label="Páginas de prácticas">{!! get_the_posts_pagination() !!}</nav>
      @else
        @include('partials.practicas-access')
      @endif
    </div>
  </section>
@endsection
