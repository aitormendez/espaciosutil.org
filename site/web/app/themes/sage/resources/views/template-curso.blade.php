{{--
  Template Name: Curso
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @include('partials.page-header')

    <div class="prose max-w-none">
      @php(the_content())
    </div>

    @if (!empty($course_index))
      <div class="course-index mt-12">
        <h2 class="mb-6 text-2xl font-bold">Índice del Curso</h2>
        <ul class="space-y-4">
          @include('partials.course-index-item', ['items' => $course_index])
        </ul>
      </div>
    @else
      <p class="mt-12">No hay contenido disponible para este curso en este momento.</p>
    @endif
  @endwhile
@endsection
