{{--
  Template Name: Curso
--}}

@extends('layouts.app')

@section('content')
    @while (have_posts())
        @php(the_post())
        @include('partials.page-header')

        <div class="border-blanco bg-morado5/90 prose md:prose-2xl flex justify-center border-t pb-12 pt-44 text-lg">
            <div class="mx-auto w-full max-w-4xl px-6 md:px-0">
                <div class="">
                    @php(the_content())
                </div>
            </div>
        </div>

        <div id="contenido" class="bg-morado5/90 flex justify-center pb-20 font-sans">
            <div class="mx-auto w-full max-w-4xl px-6 md:flex md:space-x-12 md:px-0">
                <aside class="md:w-1/3">
                    <h2 class="mb-6 font-sans text-2xl">Series</h2>
                    <ul class="space-y-2">
                        @foreach ($series_cde_lessons as $lesson)
                            @php
                                $terms = get_the_terms($lesson->ID, 'serie_cde');
                                $serieName = $terms && !is_wp_error($terms) ? $terms[0]->name : $lesson->post_title;
                            @endphp
                            <li>
                                <button data-post-id="{{ $lesson->ID }}"
                                    class="serie-cde-button bg-morado2 rounded-xs text-gris5 hover:bg-blanco w-full cursor-pointer p-3 text-left transition-colors">
                                    {{ $serieName }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </aside>
                <main class="mt-12 md:mt-0 md:w-2/3">
                    <h2 class="mb-6 font-sans text-2xl">Lecciones</h2>
                    <div id="indice-ajax-container" class="course-index prose font-sans">
                        <p>Selecciona un revelador para ver sus lecciones.</p>
                    </div>
                </main>
            </div>
        </div>
    @endwhile
@endsection
