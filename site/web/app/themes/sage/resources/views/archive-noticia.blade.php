@extends('layouts.app')

@section('content')
    @include('partials.page-header')

    <div class="content border-blanco text-blanco bg-morado5/90 relative flex flex-col border-t">
        <div
            class="posts prose prose-xl md:prose-2xl relative max-w-none !leading-tight">
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
