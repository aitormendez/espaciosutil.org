{{--
  Template Name: Series
--}}

@extends('layouts.app')

@section('content')
    @while (have_posts())
        @php the_post() @endphp
        @include('partials.page-header')
    @endwhile

    <div class="border-blanco text-blanco bg-morado5/90 relative flex justify-center border-t py-6 md:py-20">
        <div class="prose-sutil prose prose-xl md:prose-2xl relative w-full max-w-none leading-snug md:leading-snug">
            <div class="px-6">
                @includeFirst(['partials.content-page', 'partials.content'])
            </div>

            @php
                global $wp_query;
                $temp_query = $wp_query;
                $wp_query = $items_query;
            @endphp

            <div class="infinite-scroll-container series-canalizadas">
                @while (have_posts())
                    @php the_post() @endphp
                    @if (get_post_type() === 'area')
                        <article class="area infinite-scroll-item bg-negro/60 border-blanco border-t-4 px-6 md:px-0">
                            <div class="columna mx-auto max-w-5xl">
                                <a href="{{ get_permalink() }}"
                                    class="entry-title mt-6 block font-sans text-3xl font-light md:mt-20 md:text-5xl">
                                    {{ get_the_title() }}
                                </a>

                                @if (has_excerpt())
                                    <div class="excerpt mp-6 md:mb-20">{{ get_the_excerpt() }}</div>
                                @endif

                                                                        @php $series_rel = $series_relacionadas() @endphp

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
                        <article class="infinite-scroll-item columna mx-auto max-w-5xl">
                            @include('partials.content-serie')
                        </article>
                    @endif
                @endwhile
            </div>
            <div class="posts-nav">
                {!! get_the_posts_navigation() !!}
            </div>
            @php
                wp_reset_postdata();
                $wp_query = $temp_query;
            @endphp
        </div>
    </div>
@endsection
