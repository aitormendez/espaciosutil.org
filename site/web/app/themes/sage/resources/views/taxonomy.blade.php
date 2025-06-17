@extends('layouts.app')

@section('content')
    @include('partials.page-header')

    <div
        class="content relative border-t border-blanco pb-20 text-blanco prose prose-sutil max-w-none prose-xl md:prose-2xl !leading-tight bg-morado5/90">
        @php $term = get_queried_object(); @endphp

        <div class="intro-revelador max-w-4xl mx-auto w-full px-6 md:px-0 py-12">
            {!! get_field('revelador_texto', $term) !!}
        </div>

        <ul class="series-relacionadas mt-24 w-full max-w-4xl mx-auto px-6 md:px-0">
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
