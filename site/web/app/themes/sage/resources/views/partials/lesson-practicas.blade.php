@php
  $practices = \EspacioSutil\Practicas\Catalog::forLesson((int) get_the_ID());
@endphp
@if ($practices)
  <section class="bg-morado4/90 mx-auto my-10 w-full max-w-4xl rounded px-6 py-6 text-white" aria-labelledby="lesson-practices-title">
    <h2 id="lesson-practices-title" class="font-display text-2xl">Prácticas de esta lección</h2>
    @foreach ($practices as $practice)
      <details class="mt-5 border-t border-white/20 pt-4">
        <summary class="cursor-pointer text-xl">{{ get_the_title($practice) }}</summary>
        <div class="prose mt-4">{!! wpautop(wp_kses_post($practice->post_content)) !!}</div>
        <a class="mt-4 inline-block underline" href="{{ get_permalink($practice) }}">Ver ficha y lecciones relacionadas</a>
      </details>
    @endforeach
  </section>
@endif
