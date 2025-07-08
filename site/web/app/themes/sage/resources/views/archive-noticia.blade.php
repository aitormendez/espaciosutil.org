@extends('layouts.app')

@section('content')
    @include('partials.page-header')

    <div class="content relative border-t border-blanco flex flex-col text-blanco bg-morado5/90">
        <div
            class="posts relative prose prose-sutil prose-xl md:prose-2xl !leading-tight max-w-none prose-h2:font-thin prose-h2:leading-tight prose-a:font-thin">
            @while (have_posts())
                @php(the_post())
                @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
            @endwhile
        </div>

    </div>

    <div class="posts-nav">
        {!! get_the_posts_navigation() !!}
    </div>
@endsection

@section('sidebar')
    @include('sections.sidebar')
@endsection
