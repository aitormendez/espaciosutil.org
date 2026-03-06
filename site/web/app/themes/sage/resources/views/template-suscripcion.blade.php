{{--
  Template Name: Suscripción
--}}
@php
  $membership_list_items = [
      [
          'text' => 'Lecciones en video con lectura comentada y clarificación profunda.',
          'icon' => 'tabler-book-filled',
      ],
      [
          'text' => 'Subtítulos en 15 idiomas, incluyendo español e inglés.',
          'icon' => 'tabler-badge-cc-filled',
      ],
      [
          'text' => 'Video y audio con continuidad para retomar donde lo dejaste.',
          'icon' => 'tabler-bookmark-filled',
      ],
      [
          'text' => 'Refuerzo con resumen y mini app de preguntas por lección.',
          'icon' => 'tabler-school-filled',
      ],
  ];
  $que_incluye_list_items = [
      [
          'text' => 'Lecciones en formato de lectura comentada (vídeo) con división por capítulos temáticos.',
          'icon' => 'tabler-book-filled',
      ],
      [
          'text' => 'Reproductor de vídeo (recuerdan dónde te quedaste).',
          'icon' => 'tabler-brand-youtube-filled',
      ],
      [
          'text' => 'Reproductor de audio (recuerda dónde te quedaste).',
          'icon' => 'tabler-headphones-filled',
      ],
      [
          'text' => 'Subtítulos en 15 idiomas (incluye español e inglés; lista completa en la presentación del curso).',
          'icon' => 'tabler-badge-cc-filled',
      ],
      [
          'text' => 'Resumen de texto por lección',
          'icon' => 'tabler-file-description-filled',
      ],
      [
          'text' => 'Mini app con cuestionario por lección para reforzar lo aprendido.',
          'icon' => 'tabler-school-filled',
      ],
      [
          'text' => 'Marca de lección completada en cada leccion.',
          'icon' => 'tabler-eye-filled',
      ],
      [
          'text' => 'Índice estructurado.',
          'icon' => 'tabler-sitemap-filled',
      ],
      [
          'text' =>
              'Clarificación de conceptos y matices; contexto y estructura; armonización entre revelaciones cuando aplica.',
          'icon' => 'tabler-school-filled',
      ],
      [
          'text' => 'Acceso a todo el catálogo del CDE mientras dure tu suscripción',
          'icon' => 'tabler-key-filled',
      ],
  ];
@endphp

@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
    @endphp
    @include('partials.page-header', ['variant' => 'membership-landing'])

    <div class="content border-blanco/30 bg-morado5/90 relative border-t px-6 font-sans lg:px-0">
      <!-- Membresías (auto-cargadas desde PMPro) -->
      <div class="relative mx-auto w-full max-w-2xl px-6 pt-10 !leading-tight md:px-0">
        <x-item-list :items="$membership_list_items" class="text-gris1 mt-10 w-full max-w-3xl"
          item-class="flex items-center gap-3 text-left text-lg font-thin leading-snug"
          icon-class="text-cde-light h-[48px] w-[48px] shrink-0 rounded-full p-2 border border-white" />
      </div>
      <div id="planes" class="mx-auto mt-16 max-w-2xl">
        <h2 class="text-gris1 mb-3 text-4xl font-light">Elige tu frecuencia de pago</h2>
        <p class="text-gris2 font-light">Misma membresía, distinto ritmo de cobro.</p>
      </div>
      <x-pricing-table />

      <div id="acceso-series" class="mt-20">
        <div class="mx-auto max-w-2xl">
          <h2 class="text-gris1 mb-3 text-4xl font-light">A qué da acceso (series y lecciones)</h2>
          <p class="text-gris2 font-light">Series núcleo (lanzamiento):</p>
        </div>

        @if (!empty($series_cde_lessons))
          <ul class="mt-6 space-y-3">
            @foreach ($series_cde_lessons as $series)
              @php
                $seriesBlocks = $series['blocks'] ?? [];
                $blocksCount = count($seriesBlocks);
              @endphp
              <li class="bg-negro/30 border-blanco border-t font-serif last:border-b">
                <h3 class="mx-auto flex max-w-2xl items-center justify-between py-3 text-2xl font-light text-white/90">
                  <span>{{ $series['name'] }}</span>
                  <span class="text-2xl text-white/60">{{ $blocksCount }}
                    {{ $blocksCount === 1 ? 'bloque' : 'bloques' }}</span>
                </h3>

                @if (!empty($seriesBlocks))
                  <ul class="">
                    @foreach ($seriesBlocks as $block)
                      @php
                        $lessonsCount = (int) ($block['lessons_count'] ?? 0);
                      @endphp
                      <li
                        class="border-blanco/30 mx-auto flex max-w-2xl items-center justify-between gap-3 border-t py-2 text-2xl text-white/80">
                        <span class="flex-1 text-left">{{ $block['name'] }}</span>
                        <span class="whitespace-nowrap text-right text-white/60">{{ $lessonsCount }}
                          {{ $lessonsCount === 1 ? 'lección' : 'lecciones' }}</span>
                      </li>
                    @endforeach
                  </ul>
                @else
                  <p class="mx-auto max-w-2xl px-4 pb-4 text-sm text-white/60">Esta serie aún no tiene bloques
                    disponibles.</p>
                @endif
              </li>
            @endforeach
          </ul>
        @else
          <p class="rounded-xs mx-auto mt-4 max-w-2xl bg-white/5 px-4 py-3 text-sm text-white/70">No hay series
            disponibles todavía.</p>
        @endif

        <p class="text-gris2 mx-auto mt-6 max-w-2xl font-light"><span class="font-semibold text-white/90">Nuevos
            contenidos:</span>
          se irán incorporando semanalmente nuevas series, bloques y lecciones dentro de la membresía única.</p>
      </div>

      <div id="que-incluye" class="mx-auto mt-16 max-w-2xl">
        <h2 class="text-gris1 mb-3 text-4xl font-light">Qué incluye</h2>
      </div>
      <div class="relative mx-auto w-full max-w-2xl px-6 pt-10 !leading-tight md:px-0">
        <x-item-list :items="$que_incluye_list_items" class="text-gris1 mt-10 w-full max-w-3xl"
          item-class="flex items-center gap-3 text-left text-lg font-thin leading-snug"
          icon-class="text-cde-light h-[48px] w-[48px] shrink-0 rounded-full p-2 border border-white" />
      </div>
      @includeFirst(['partials.content-page', 'partials.content'])


    </div>
  @endwhile
@endsection
