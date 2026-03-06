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
@endphp

@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php(the_post())
    @include('partials.page-header', ['variant' => 'membership-landing'])

    <div class="content border-blanco bg-morado5/90 relative border-t px-6 font-sans lg:px-0">
      <!-- Membresías (auto-cargadas desde PMPro) -->
      <div class="flex justify-center">
        <div class="relative w-full max-w-4xl px-6 pt-10 !leading-tight md:px-0">
          <x-item-list :items="$membership_list_items" class="text-gris1 mt-10 w-full max-w-3xl"
            item-class="flex items-center gap-3 text-left text-lg font-thin leading-snug"
            icon-class="text-cde-light h-[48px] w-[48px] shrink-0 rounded-full p-2 border border-white" />
          @includeFirst(['partials.content-page', 'partials.content'])
        </div>
      </div>
      <x-pricing-table />


    </div>
  @endwhile
@endsection
