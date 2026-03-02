@extends('layouts.app')

@section('content')
    @include('partials.page-header')

    <div
        class="content border-blanco text-blanco prose prose-xl md:prose-2xl bg-morado5/90 relative max-w-none border-t pb-20 !leading-tight">
        @php $term = get_queried_object(); @endphp

        <div class="intro-revelador mx-auto w-full max-w-4xl px-6 py-12 md:px-0">
            {!! get_field('revelador_texto', $term) !!}
        </div>

        <ul class="series-relacionadas mx-auto mt-24 w-full max-w-4xl px-6 md:px-0">
            @while (have_posts())
                @php(the_post())
                @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
            @endwhile
        </ul>

    </div>
@endsection

@section('sidebar')
    @include('sections.sidebar')
@endsection
