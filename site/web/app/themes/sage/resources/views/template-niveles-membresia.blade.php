{{-- 
  Template Name: Niveles de Membresía (Personalizado)
--}}

@extends('layouts.app')

@section('content')
    @while (have_posts())
        @php(the_post())
        @include('partials.page-header')

        <div class="content border-blanco bg-morado5/90 relative border-t px-6 pt-44 font-sans lg:px-0">
            <!-- Membresías (auto-cargadas desde PMPro) -->
            <x-pricing-table />

            <div class="flex justify-center">
                <div class="relative w-full max-w-4xl px-6 pt-10 !leading-tight md:px-0">
                    @includeFirst(['partials.content-page', 'partials.content'])
                </div>
            </div>

        </div>
    @endwhile
@endsection
