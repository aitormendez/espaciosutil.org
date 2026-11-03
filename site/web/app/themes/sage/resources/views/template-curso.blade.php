{{--
  Template Name: Curso
--}}

@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
      $hasEditorContent = trim((string) get_the_content()) !== '';
    @endphp
    <div class="course-family-index font-sans text-blanco">
      {{-- The existing constellation canvas remains visible through the hero;
           the family sections below paint opaque backgrounds. --}}
      <header class="course-index-hero pt-[4.75rem] pb-16 md:pb-0">
        <h1 class="m-0 px-5 text-center text-[clamp(2rem,3.3vw,3.25rem)] leading-[1.2] font-light">{{ wp_strip_all_tags(wp_specialchars_decode((string) get_the_title(), ENT_QUOTES)) }}</h1>
        @if ($hasEditorContent)
          <div class="course-index-introduction prose-cde mx-auto my-12 max-w-4xl bg-cde p-6">
            @php the_content(); @endphp
          </div>
        @endif
        <div class="course-family-grid course-index-columns mx-auto hidden w-full max-w-[100rem] grid-cols-1 gap-7 px-[clamp(1.25rem,3.5vw,3.5rem)] md:mt-40 md:grid md:grid-cols-[minmax(10rem,0.85fr)_minmax(12rem,1fr)_minmax(0,3fr)] md:gap-x-[clamp(1.5rem,3vw,3rem)] md:pb-3 md:text-[1.375rem] md:leading-normal" aria-hidden="true">
          <span></span>
          <span>Series</span>
          <span>Temas</span>
        </div>
      </header>

      <div id="contenido">
        @forelse ($course_family_groups as $group)
          @include('partials.course-family-section', ['group' => $group])
        @empty
          <p class="course-index-empty bg-cde px-5 py-12 text-center">No hay series disponibles.</p>
        @endforelse
      </div>
    </div>
  @endwhile
@endsection
