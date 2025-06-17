{{--
  Template Name: Reveladores
--}}

@extends('layouts.app')

@section('content')
    @while (have_posts())
        @php(the_post())
        @include('partials.page-header')
        <div class="relative border-t border-blanco flex justify-center py-6 md:py-20 text-blanco bg-morado5/90">
            <div class="relative w-full prose-sutil prose max-w-none prose-xl md:prose-2xl !leading-tight">
                <div class="max-w-4xl mx-auto w-full px-6 md:px-0">
                    @include('partials.content-page')
                </div>

                @if (is_page('reveladores'))
                    @set($terms, get_terms('revelador'))
                @elseif (is_page('canales'))
                    @set($terms, get_terms('canal'))
                @endif

                <div class="lista w-full border-y border-gris4 not-prose">
                    <ul class="not-prose font-sans text-4xl max-w-4xl mx-auto">
                        @foreach ($terms as $term)
                            <li class="border-b border-gris4 last:border-b-0 list-none">
                                <a class="block py-4 text-morado2 hover:text-blanco px-6 md:px-0"
                                    href="{{ get_term_link($term->term_id) }}">{{ $term->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endwhile
@endsection
