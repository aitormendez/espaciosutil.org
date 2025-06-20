@extends('layouts.app')

@section('content')
    @while (have_posts())
        @php(the_post())
        @include('partials.page-header')

        <div class="content border-blanco bg-morado5/90 relative border-t px-6 lg:px-0">

            @if (get_field('has_toc'))
                <div class="toc-wrap sticky top-40 z-50 mt-12">
                    <div id="toc" class="absolute max-w-xs overflow-hidden font-sans font-thin"
                        data-toc={{ get_field('has_toc') }}>
                        <div class="js-toc hidden md:block"></div>
                    </div>
                </div>
                <div class="flex items-start">
                    <div id="toc-content" class="relative flex w-full justify-center pr-6 md:ml-80">
                        <div
                            class="main-content-wrap prose prose-sutil prose-xl lg:prose-2xl w-full max-w-3xl !leading-tight">
                            @includeFirst(['partials.content-page', 'partials.content'])
                        </div>
                    </div>
                </div>
            @else
                <div class="flex justify-center">
                    <div
                        class="prose prose-sutil prose-xl lg:prose-2xl relative w-full max-w-4xl px-6 pt-20 !leading-tight md:px-0">
                        @includeFirst(['partials.content-page', 'partials.content'])
                    </div>
                </div>
            @endif
        </div>
    @endwhile
@endsection
