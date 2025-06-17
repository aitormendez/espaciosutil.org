{{--
  Template Name: Series
--}}

@extends('layouts.app')

@section('content')
    @while (have_posts())
        @php the_post() @endphp
        @include('partials.page-header')
    @endwhile

    <div class="relative border-t border-blanco flex justify-center py-6 md:py-20 text-blanco bg-morado5/90">
        <div class="relative w-full prose-sutil prose max-w-none prose-xl md:prose-2xl leading-snug md:leading-snug">
            <div class="px-6">
                @includeFirst(['partials.content-page', 'partials.content'])
            </div>

            @php
                global $wp_query;
                $temp_query = $wp_query;
                $wp_query = null;
                $wp_query = $items_query;
            @endphp

            <div class="infinite-scroll-container series-canalizadas">
                @posts($items_query)
                    @if (get_post_type() === 'area')
                        <article class="area infinite-scroll-item bg-negro/60 border-blanco border-t-4 px-6 md:px-0">
                            <div class="columna max-w-5xl mx-auto">
                                <a href="@permalink"
                                    class="entry-title mt-6 md:mt-20 block font-sans text-3xl md:text-5xl font-light">
                                    @title
                                </a>

                                @if (has_excerpt())
                                    <div class="excerpt mp-6 md:mb-20">@excerpt</div>
                                @endif

                                @set($series_rel, $series_relacionadas())

                                @if ($series_rel)
                                    <ul class="series-relacionadas !m-0">
                                        @foreach ($series_rel as $serie)
                                            @include('partials.serie-relacionada')
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </article>
                    @endif
                    @if (get_post_type() === 'serie')
                        <article class="infinite-scroll-item columna max-w-5xl mx-auto">
                            @include('partials.content-serie')
                        </article>
                    @endif
                @endposts
            </div>
            <div class="posts-nav">
                {!! get_the_posts_navigation() !!}
            </div>
            @php
                $wp_query = $temp_query;
            @endphp
        </div>
    </div>
@endsection
