{{--
  Template Name: CDE Hub
--}}
@php
  $free_lesson_url = home_url('/leccion-gratuita/');
  $membership_url = get_permalink(2242) ?: home_url('/suscripcion/');
  $lesson_index_url = get_permalink(2648) ?: home_url('/indice-de-lecciones/');
  $telegram_url = 'https://t.me/+RJCRMR-axzzgR2Ej';

  $hero_list_items = [
      [
          'text' => 'Lecciones en vídeo con lectura comentada y explicaciones paso a paso.',
          'icon' => 'tabler-book-filled',
      ],
      [
          'text' => 'Mapas, series y navegación para saber dónde estás y por dónde seguir.',
          'icon' => 'tabler-binary-tree-filled',
      ],
      [
          'text' => 'Nuevas lecciones semanales dentro de una biblioteca en expansión.',
          'icon' => 'tabler-bookmark-filled',
      ],
      [
          'text' => 'Recursos de apoyo para estudiar con más contexto y fijar mejor lo aprendido.',
          'icon' => 'tabler-school-filled',
      ],
  ];
  $hub_actions = [
      [
          'title' => 'Ver lección gratuita',
          'description' =>
              'La mejor puerta de entrada si todavía estás conociendo el proyecto. Te permite ver el enfoque real antes de decidir.',
          'href' => $free_lesson_url,
          'icon' => 'tabler-arrow-badge-right-filled',
      ],
      [
          'title' => 'Suscripción',
          'description' => 'Acceso completo al CDE para quien ya conoce el enfoque y quiere profundizar sin esperar.',
          'href' => $membership_url,
          'icon' => 'tabler-key-filled',
      ],
      [
          'title' => 'Explorar series y lecciones',
          'description' =>
              'Entrada al Índice de lecciones para ubicarte, recorrer las series y entender cómo está organizado el contenido.',
          'href' => $lesson_index_url,
          'icon' => 'tabler-binary-tree-filled',
      ],
      [
          'title' => 'Unirme a Telegram',
          'description' => 'Canal de avisos, recordatorios y novedades para seguir el ritmo del proyecto.',
          'href' => $telegram_url,
          'icon' => 'tabler-arrow-badge-right-filled',
          'target' => '_blank',
          'rel' => 'noopener noreferrer',
      ],
  ];
  $includes_list_items = [
      [
          'text' => 'Lecciones en formato de lectura comentada en vídeo, con división por capítulos temáticos.',
          'icon' => 'tabler-book-filled',
      ],
      [
          'text' => 'Reproductor de vídeo y de audio; ambos recuerdan dónde te quedaste.',
          'icon' => 'tabler-headphones-filled',
      ],
      [
          'text' => 'Subtítulos en 15 idiomas para estudiar con más comodidad.',
          'icon' => 'tabler-badge-cc-filled',
      ],
      [
          'text' => 'Resumen de texto por lección para consolidar ideas clave.',
          'icon' => 'tabler-file-description-filled',
      ],
      [
          'text' => 'Cuestionario por lección para reforzar lo aprendido.',
          'icon' => 'tabler-school-filled',
      ],
      [
          'text' => 'Marca de lección completada e índice estructurado para orientarte dentro del curso.',
          'icon' => 'tabler-binary-tree-filled',
      ],
      [
          'text' => 'Clarificación de conceptos, contexto, estructura y armonización entre revelaciones cuando aplica.',
          'icon' => 'tabler-sparkles',
      ],
      [
          'text' => 'Acceso a todo el catálogo del CDE mientras dure tu suscripción.',
          'icon' => 'tabler-key-filled',
      ],
  ];
  $trust_list_items = [
      [
          'text' => 'Archivo público amplio: Espacio Sutil cuenta con más de 1600 vídeos publicados.',
          'icon' => 'tabler-arrow-badge-right-filled',
      ],
      [
          'text' => 'Método visible: no se promete transformación rápida, sino comprensión con estructura.',
          'icon' => 'tabler-arrow-badge-right-filled',
      ],
      [
          'text' => 'Continuidad real: el curso crece con nuevas lecciones semanales.',
          'icon' => 'tabler-arrow-badge-right-filled',
      ],
  ];
  $getting_started_list_items = [
      [
          'text' => 'Si eres nuevo, empieza por la lección gratuita.',
          'icon' => 'tabler-circle-number-1-filled',
      ],
      [
          'text' => 'Si ya conoces el enfoque, entra en Suscripción.',
          'icon' => 'tabler-circle-number-2-filled',
      ],
      [
          'text' => 'Si quieres ubicarte primero, entra al Índice de lecciones.',
          'icon' => 'tabler-circle-number-3-filled',
      ],
      [
          'text' => 'Si quieres seguir las novedades, únete a Telegram.',
          'icon' => 'tabler-circle-number-4-filled',
      ],
  ];
@endphp

@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
    @endphp

    @include('partials.page-header', ['variant' => 'cde-hub-landing'])

    <div class="content border-blanco/30 bg-morado5/90 relative border-t pb-40 pt-6 font-sans lg:px-0 lg:pt-40">
      <div class="relative mx-auto w-full max-w-2xl px-6 pt-10 !leading-tight md:px-0">
        <x-item-list :items="$hero_list_items" class="text-gris1 mt-10 w-full max-w-3xl"
          item-class="flex items-center gap-3 text-left text-lg font-thin leading-snug"
          icon-class="text-cde-light h-[48px] w-[48px] shrink-0 rounded-full border border-white p-2" />
        <div class="membership-hero-ctas mt-12 flex flex-col justify-center gap-4 sm:flex-row">
          <x-cta href="{{ $free_lesson_url }}" text="Ver lección gratuita"
            clases="bg-cde hover:text-morado5 font-semibold text-gray-200" icon="tabler-eye-filled" />
          <x-cta href="{{ $membership_url }}" text="Suscripción"
            clases="bg-cde/50 hover:text-morado5 font-semibold text-gray-200" icon="tabler-key-filled" />
        </div>
        <div class="mt-8 text-center">
          <a href="{{ $lesson_index_url }}"
            class="text-gris1 hover:text-cde inline-flex items-center gap-2 text-lg font-light underline decoration-white/30 underline-offset-4 transition-colors">
            Explorar series y lecciones
            <span aria-hidden="true">→</span>
          </a>
        </div>
        <p class="text-gris2 mt-8 text-center text-lg font-light leading-relaxed">
          Empieza gratis con una lección completa, accede a todo el contenido con la membresía o entra al Índice de
          lecciones para orientarte.
        </p>
      </div>

      <section id="que-es-el-cde" class="mx-auto mt-20 max-w-2xl px-6 md:px-0">
        <h2 class="text-gris1 mb-4 text-4xl font-light">Qué es el CDE</h2>
        <div class="text-gris2 space-y-4 text-lg font-light leading-relaxed">
          <p>
            El Curso de Desarrollo Espiritual es una propuesta de estudio para comprender textos revelados complejos con
            un método claro.
          </p>
          <p>
            Aquí no se busca motivación pasajera ni frases sueltas sin contexto. El foco está en clarificar conceptos y
            matices, dar estructura al recorrido y relacionar ideas entre textos cuando eso ayuda a comprender mejor.
          </p>
        </div>
      </section>

      <section id="que-puedes-hacer" class="mx-auto mt-20 max-w-5xl px-6 md:px-0">
        <h2 class="text-gris1 mb-8 text-center text-4xl font-light">Qué puedes hacer desde aquí</h2>
        <div class="grid gap-6 md:grid-cols-2">
          @foreach ($hub_actions as $action)
            <article class="bg-negro/25 rounded border border-white/30 p-6">
              <h3 class="text-gris1 text-2xl font-light">{{ $action['title'] }}</h3>
              <p class="text-gris2 mt-4 text-lg font-light leading-relaxed">{{ $action['description'] }}</p>
              <a href="{{ $action['href'] }}"
                @if (!empty($action['target'])) target="{{ $action['target'] }}" rel="{{ $action['rel'] ?? 'noopener noreferrer' }}" @endif
                class="text-cde-light decoration-current/30 mt-6 inline-flex items-center gap-2 text-lg font-medium underline underline-offset-4">
                {{ $action['title'] }}
                <span aria-hidden="true">→</span>
              </a>
            </article>
          @endforeach
        </div>
      </section>

      <section id="que-incluye" class="mx-auto mt-20 max-w-2xl px-6 md:px-0">
        <h2 class="text-gris1 mb-8 text-4xl font-light">Qué incluye</h2>
        <x-item-list :items="$includes_list_items" class="text-gris1 mt-10 w-full max-w-3xl"
          item-class="flex items-center gap-3 text-left text-lg font-thin leading-snug"
          icon-class="text-cde-light h-[48px] w-[48px] shrink-0 rounded-full border border-white p-2" />
      </section>

      <section id="series-nucleo" class="mx-auto mt-20 max-w-2xl px-6 md:px-0">
        <h2 class="text-gris1 mb-8 text-4xl font-light">Series núcleo</h2>
        <div class="space-y-10">
          <div>
            <h3 class="text-gris1 text-2xl font-light">Urantia</h3>
            <p class="text-gris2 mt-4 text-lg font-light leading-relaxed">
              Lectura guiada, contexto y clarificación progresiva para no perderse en la amplitud del texto.
            </p>
          </div>
          <div>
            <h3 class="text-gris1 text-2xl font-light">Seth</h3>
            <p class="text-gris2 mt-4 text-lg font-light leading-relaxed">
              Trabajo de conceptos, distinciones y matices para una comprensión menos superficial.
            </p>
          </div>
          <div>
            <p class="text-gris2 mt-4 text-lg font-light leading-relaxed">
              Puedes empezar por la serie que más te llame o usar el Índice de lecciones para orientarte antes de entrar.
            </p>
          </div>
        </div>
        <div class="mt-10 flex justify-center">
          <x-cta href="{{ $lesson_index_url }}" text="Explorar series y lecciones"
            clases="bg-white/5 hover:text-morado5 font-semibold text-gray-200" icon="tabler-binary-tree-filled" />
        </div>
      </section>

      <section id="por-que-confiar" class="mx-auto mt-20 max-w-2xl px-6 md:px-0">
        <h2 class="text-gris1 mb-8 text-4xl font-light">Por qué confiar en este enfoque</h2>
        <x-item-list :items="$trust_list_items" class="text-gris1 mt-10 w-full max-w-3xl"
          item-class="flex items-center gap-3 text-left text-lg font-thin leading-snug"
          icon-class="text-cde-light h-[48px] w-[48px] shrink-0 rounded-full border border-white p-2" />
      </section>

      <section id="como-empezar" class="mx-auto mt-20 max-w-2xl px-6 md:px-0">
        <h2 class="text-gris1 mb-8 text-4xl font-light">Cómo empezar</h2>
        <x-item-list :items="$getting_started_list_items" class="text-gris1 mt-10 w-full max-w-3xl"
          item-class="flex items-center gap-3 text-left text-lg font-thin leading-snug"
          icon-class="text-cde-light h-[48px] w-[48px] shrink-0 rounded-full border border-white p-2" />
      </section>

      <div class="mx-auto mt-16 max-w-2xl px-6 md:px-0">
        <h2 class="text-gris1 mb-4 text-4xl font-light">Cierre</h2>
        <p class="text-gris2 text-lg font-light leading-relaxed">
          Si buscas comprender estos textos con más claridad y más estructura, aquí tienes una forma ordenada de empezar.
        </p>
      </div>

      <div class="mx-auto mt-12 grid w-full max-w-5xl gap-4 px-6 sm:grid-cols-2 md:px-0 xl:grid-cols-4">
        <x-cta href="{{ $free_lesson_url }}" text="Ver lección gratuita"
          clases="bg-cde hover:text-morado5 font-semibold text-gray-200" icon="tabler-eye-filled" />
        <x-cta href="{{ $membership_url }}" text="Suscripción"
          clases="bg-cde/50 hover:text-morado5 font-semibold text-gray-200" icon="tabler-key-filled" />
        <x-cta href="{{ $lesson_index_url }}" text="Explorar series y lecciones"
          clases="bg-white/5 hover:text-morado5 font-semibold text-gray-500" icon="tabler-binary-tree-filled" />
        <x-cta href="{{ $telegram_url }}" text="Unirme a Telegram" target="_blank" rel="noopener noreferrer"
          clases="bg-white/5 hover:text-morado5 font-semibold text-gray-500" icon="tabler-arrow-badge-right-filled" />
      </div>
    </div>
  @endwhile
@endsection
