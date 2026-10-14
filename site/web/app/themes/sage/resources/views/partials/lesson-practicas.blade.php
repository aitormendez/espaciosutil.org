@php $practices = \EspacioSutil\Practicas\Catalog::forLesson((int) get_the_ID()); @endphp
@if ($practices)
  <section class="practice-lesson-section practice-container" aria-label="Prácticas de esta lección">
    @foreach ($practices as $practice)
      <header class="practice-embedded-header"><p class="practice-eyebrow">Práctica</p><a href="{{ get_permalink($practice) }}">Abrir práctica por separado @include('partials.practice-icon', ['icon'=>'right'])</a></header>
      <h2>{{ get_the_title($practice) }}</h2>
      @include('partials.practice-viewer', ['practice'=>$practice, 'embedded'=>true])
    @endforeach
    <a class="practice-back" href="{{ get_post_type_archive_link('practica') }}">Ver todas las prácticas @include('partials.practice-icon', ['icon'=>'right'])</a>
  </section>
@endif
