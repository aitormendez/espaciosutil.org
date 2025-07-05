{{--
  Template Name: Curso
--}}

@extends('layouts.app')

@section('content')
    @while (have_posts())
        @php(the_post())
        @include('partials.page-header')

        <div class="border-blanco bg-morado5/90 prose flex justify-center border-t py-12 text-lg">
            <div class="mx-auto w-full max-w-4xl px-6 md:px-0">
                <div class="">
                    @php(the_content())
                </div>
            </div>
        </div>

        @if (!empty($course_index))
            <div class="border-blanco bg-morado5/90 flex justify-center border-t py-12 font-sans">
                <div class="mx-auto w-full max-w-4xl px-6 md:px-0">
                    <div class="course-index prose font-sans">
                        <h2 class="mb-6">Índice del Curso</h2>

                        @include('partials.course-index-item', [
                            'items' => $course_index,
                            'level' => 0,
                        ])

                    </div>
                </div>
            </div>
        @else
            <p class="mt-12">No hay contenido disponible para este curso en este momento.</p>
        @endif
    @endwhile
@endsection
