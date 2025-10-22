{{--
  Template Name: Curso
--}}

@extends('layouts.app')

@section('content')
    @while (have_posts())
        @php
            the_post();
        @endphp
        @include('partials.page-header')

        <div class="border-blanco bg-morado5/90 prose md:prose-2xl flex justify-center border-t pb-12 pt-44 text-lg">
            <div class="mx-auto w-full max-w-4xl px-6 md:px-0">
                <div class="">
                    @php
                        the_content();
                    @endphp
                </div>
            </div>
        </div>

        <div id="contenido" class="bg-morado5/90 flex justify-center pb-20 font-sans">
            <div class="mx-auto w-full max-w-4xl px-6 md:flex md:space-x-12 md:px-0">
                <aside class="md:w-1/3">
                    <h2 class="mb-6 font-sans text-2xl">Series</h2>
                    <ul class="space-y-3">
                        @foreach ($series_cde_lessons as $series)
                            @php
                                $seriesTermId = $series['term_id'] ?? null;
                                $seriesBlocks = $series['blocks'] ?? [];
                                $seriesSlug = sanitize_title($series['name']);
                                $seriesButtonId = $seriesTermId
                                    ? 'serie-toggle-' . $seriesTermId
                                    : 'serie-toggle-' . $seriesSlug;
                                $seriesPanelId = $seriesTermId
                                    ? 'serie-panel-' . $seriesTermId
                                    : $seriesButtonId . '-panel';
                            @endphp
                            <li class="serie-accordion-item">
                                <button type="button"
                                    class="serie-accordion-toggle bg-morado2 rounded-xs text-gris5 hover:bg-blanco flex w-full items-center justify-between px-3 py-2 text-left font-semibold transition-colors"
                                    data-series-term="{{ $seriesTermId }}" aria-expanded="false"
                                    aria-controls="{{ $seriesPanelId }}" id="{{ $seriesButtonId }}">
                                    <span>{{ $series['name'] }}</span>
                                    <span class="serie-accordion-icon transition-transform duration-200">+</span>
                                </button>

                                <div id="{{ $seriesPanelId }}" class="serie-accordion-panel hidden" role="region"
                                    aria-labelledby="{{ $seriesButtonId }}">
                                    @if (!empty($seriesBlocks))
                                        <ul class="mt-2 space-y-2">
                                            @foreach ($seriesBlocks as $block)
                                                <li>
                                                    <button type="button"
                                                        class="serie-cde-button bg-morado4/80 hover:bg-morado1/80 rounded-xs text-blanco flex w-full cursor-pointer px-3 py-2 text-left transition-colors"
                                                        data-post-id="{{ $block['post_id'] }}"
                                                        data-block-term="{{ $block['term_id'] }}">
                                                        <span>{{ $block['name'] }}</span>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="rounded-xs bg-morado4/50 text-gris5 mt-2 p-3 text-sm">No hay bloques
                                            disponibles.</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </aside>
                <main class="mt-12 md:mt-0 md:w-2/3">
                    <h2 class="mb-6 font-sans text-2xl">Temas</h2>
                    <div id="indice-ajax-container" class="course-index prose font-sans">
                        <p>Selecciona un bloque de la serie para ver sus temas.</p>
                    </div>
                </main>
            </div>
        </div>
    @endwhile
@endsection
