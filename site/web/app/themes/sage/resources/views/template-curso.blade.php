{{--
  Template Name: Curso
--}}

@extends('layouts.app')

@section('content')
    @while (have_posts())
        @php(the_post())
        @include('partials.page-header')

        <div class="border-blanco bg-morado5/90 prose md:prose-2xl flex justify-center border-t py-12 text-lg">
            <div class="mx-auto w-full max-w-4xl px-6 md:px-0">
                <div class="">
                    @php(the_content())
                </div>
            </div>
        </div>

        <div class="border-blanco bg-morado5/90 flex justify-center border-t py-12 font-sans">
            <div class="mx-auto w-full max-w-4xl px-6 md:flex md:space-x-12 md:px-0">
                <aside class="md:w-1/3">
                    <h2 class="mb-6 font-sans text-2xl">Reveladores</h2>
                    <ul class="space-y-2">
                        @foreach ($revelador_lessons as $lesson)
                            <li>
                                <button data-post-id="{{ $lesson->ID }}" class="revelador-button w-full rounded-md bg-morado3 p-3 text-left text-white hover:bg-morado2">
                                    {{ get_the_terms($lesson->ID, 'revelador')[0]->name }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </aside>
                <main class="md:w-2/3 mt-12 md:mt-0">
                    <h2 class="mb-6 font-sans text-2xl">Lecciones</h2>
                    <div id="indice-ajax-container" class="course-index prose font-sans">
                        <p>Selecciona un revelador para ver sus lecciones.</p>
                    </div>
                </main>
            </div>
        </div>
    @endwhile
@endsection
