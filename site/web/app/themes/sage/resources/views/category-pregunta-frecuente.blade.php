@extends('layouts.app')

@section('content')
    @include('partials.page-header')

    <div
        class="content relative border-t border-blanco pb-20 text-blanco prose prose-sutil max-w-none text-2xl bg-morado5/90">

        <ul class="series-relacionadas mt-24 w-full max-w-4xl mx-auto px-6 md:px-0 prose prose-sutil prose-li:my-0">
            @while (have_posts())
                @php(the_post())
                <li class="list-none pl-0 text-3xl border-t border-gris3 last:border-b py-3">
                    <a href="{{ get_permalink() }}">
                        {{ get_the_title() }}
                    </a>
                </li>
            @endwhile
        </ul>

    </div>
@endsection

@section('sidebar')
    @include('sections.sidebar')
@endsection
