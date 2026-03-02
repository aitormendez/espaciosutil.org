@extends('layouts.app')

@section('content')
    @include('partials.page-header')

    <div class="content border-blanco text-blanco bg-morado5/90 relative flex flex-col border-t px-6 py-20">
        <div id="calendario" class="relative w-full font-sans text-xs md:text-lg"></div>

        <div class="posts prose relative mt-24">
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
