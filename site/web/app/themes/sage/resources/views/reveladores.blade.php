{{--
  Template Name: Reveladores
--}}

@extends('layouts.app')

@section('content')
    @while (have_posts())
        @php the_post() @endphp
        @include('partials.page-header')
        <div class="border-blanco text-blanco bg-morado5/90 relative flex justify-center border-t py-6 md:py-20">
            <div class="prose-sutil prose prose-xl md:prose-2xl relative w-full max-w-none !leading-tight">
                <div class="mx-auto w-full max-w-4xl px-6 md:px-0">
                    @include('partials.content-page')
                </div>

                @if (is_page('reveladores'))
                    @php $terms = get_terms('revelador') @endphp
                @elseif (is_page('canales'))
                    @php $terms = get_terms('canal') @endphp
                @endif

                <div class="lista border-gris4 not-prose w-full border-y">
                    <ul class="not-prose mx-auto max-w-4xl font-sans text-4xl">
                        @foreach ($terms as $term)
                            <li class="border-gris4 list-none border-b last:border-b-0">
                                <a class="text-morado2 hover:text-blanco block px-6 py-4 md:px-0"
                                    href="{{ get_term_link($term->term_id) }}">{{ $term->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endwhile
@endsection
