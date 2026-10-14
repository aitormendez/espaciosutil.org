<article class="practice-page">
  <div class="practice-container">
    <a class="practice-back" href="{{ get_post_type_archive_link('practica') }}">@include('partials.practice-icon', ['icon'=>'left']) Todas las prácticas</a>
    @if (\EspacioSutil\Practicas\Catalog::allowed() || \EspacioSutil\Practicas\Catalog::editor())
      <h1>{{ get_the_title() }}</h1>
      @if (post_password_required())
        {!! get_the_password_form() !!}
      @else
        <p class="practice-description">{{ get_the_excerpt() }}</p>
        @include('partials.practice-viewer', ['practice'=>get_post(), 'embedded'=>false])
        @php $lessons = \EspacioSutil\Practicas\Catalog::lessonsFor(get_the_ID()); @endphp
        @if ($lessons)
          <aside class="practice-context"><h2>Esta práctica aparece en</h2><div class="practice-lesson-links">
            @foreach ($lessons as $lesson)
              <a class="practice-lesson-link" href="{{ get_permalink($lesson) }}">{{ get_the_title($lesson) }}</a>
            @endforeach
          </div></aside>
        @endif
      @endif
    @else
      <h1>Prácticas</h1>
      @include('partials.practicas-access')
    @endif
  </div>
</article>
