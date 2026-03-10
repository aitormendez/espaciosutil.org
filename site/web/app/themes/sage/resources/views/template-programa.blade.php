{{--
  Template Name: Programa
--}}

@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
    @endphp
    @include('partials.page-header')

    <div class="border-blanco/30 bg-morado5/90 relative border-t pb-40 pt-14 lg:pt-20">
      <section id="programa-apertura" class="">
        @include('partials.programa.block-1')
      </section>

      <section id="programa-fases" class="mt-24">
        @include('partials.programa.block-2')
      </section>

      <section id="programa-areas" class="mt-24">
        @include('partials.programa.block-3')
      </section>

      <section id="programa-metodo" class="mt-24">
        @include('partials.programa.block-4')
      </section>

      <section id="programa-herramientas" class="mt-24">
        @include('partials.programa.block-5')
      </section>

      <section id="programa-enfoque-practico" class="mt-24">
        @include('partials.programa.block-6')
      </section>
    </div>
  @endwhile
@endsection
