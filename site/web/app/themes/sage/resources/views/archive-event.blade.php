@extends('layouts.app')

@section('content')
    @include('partials.page-header')

    <div class="content relative border-t border-blanco flex flex-col py-20 px-6 text-blanco bg-morado5/90">
        <div id="calendario" class="relative w-full font-sans text-xs md:text-lg"></div>

        <div class="posts relative prose prose-sutil mt-24">
            <h2 class="">Próximos eventos</h2>
            @while (have_posts())
                @php(the_post())
                @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
            @endwhile
        </div>

    </div>
@endsection

@section('sidebar')
    @include('sections.sidebar')
@endsection
