@extends('layouts.app')

@section('content')
  @include('partials.page-header')

  <div class="content border-blanco text-blanco prose bg-morado5/90 relative max-w-none border-t pb-20 text-2xl">

    <ul class="series-relacionadas prose mx-auto mt-24 w-full max-w-4xl px-6 md:px-0">
      @while (have_posts())
        @php(the_post())
        <li class="border-gris3 list-none border-t py-3 pl-0 text-3xl last:border-b">
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
