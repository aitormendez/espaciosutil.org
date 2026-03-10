@php
  $lessonIndexUrl = get_permalink(2648) ?: home_url('/indice-de-lecciones/');
@endphp

<div class="prose-cde mx-auto max-w-3xl px-6 lg:px-0">
  <h2>Un programa para estudiar, integrar y profundizar</h2>
  <p>
    El CDE está concebido como una arquitectura de estudio y desarrollo espiritual construida con orden, continuidad y
    vocación de integración. Su objetivo no es acumular contenidos, sino ofrecer un marco real de comprensión para
    quien quiera trabajar estos materiales con profundidad.
  </p>
</div>

<div class="mt-12 flex justify-center px-6 lg:px-0">
  <x-cta href="{{ $lessonIndexUrl }}" text="Explorar series y lecciones"
    clases="bg-cde/50 hover:text-morado5 font-semibold text-gray-200 block py-6" icon="tabler-directions-filled"
    iconClases="h-14 w-14 my-6" />
</div>
