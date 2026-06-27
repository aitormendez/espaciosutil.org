{{--
  Template Name: Atlas
--}}

@php
  $atlas_access = function_exists('espaciosutil_atlas_cde_access_state')
      ? espaciosutil_atlas_cde_access_state()
      : [
          'state' => 'unavailable',
          'grants_atlas' => false,
          'login_url' => wp_login_url(home_url('/atlas/')),
          'subscription_url' => home_url('/suscripcion/'),
          'account_url' => home_url('/cuenta-de-membresia/'),
          'atlas_url' => '',
      ];

  $state = (string) ($atlas_access['state'] ?? 'unavailable');
@endphp

@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
    @endphp

    @include('partials.page-header')

    <div class="border-blanco/30 bg-morado5/90 relative border-t px-6 pb-40 pt-16 font-sans lg:px-0 lg:pt-24">
      <section class="mx-auto max-w-3xl">
        @if ($state === 'active')
          <p class="text-cde-light text-sm font-semibold uppercase tracking-normal">Acceso activo</p>
          <h2 class="text-gris1 mt-4 text-4xl font-light">Atlas esta incluido en tu membresia CDE.</h2>
          <p class="text-gris2 mt-6 text-xl font-light leading-relaxed">
            Puedes entrar con tu acceso actual del Curso de Desarrollo Espiritual.
          </p>
          <div class="mt-10 flex flex-col gap-4 sm:flex-row">
            <x-cta href="{{ $atlas_access['atlas_url'] }}" text="Abrir Atlas"
              clases="bg-cde hover:text-morado5 font-semibold text-gray-200" icon="tabler-arrow-badge-right-filled" />
            <x-cta href="{{ $atlas_access['account_url'] }}" text="Mi cuenta"
              clases="bg-cde/40 hover:text-morado5 font-semibold text-gray-200" icon="tabler-user-filled" />
          </div>
        @elseif ($state === 'inactive')
          <p class="text-cde-light text-sm font-semibold uppercase tracking-normal">Acceso no activo</p>
          <h2 class="text-gris1 mt-4 text-4xl font-light">Atlas requiere una membresia CDE activa.</h2>
          <p class="text-gris2 mt-6 text-xl font-light leading-relaxed">
            Tu cuenta esta iniciada, pero no tiene ahora mismo una membresia que conceda acceso a Atlas.
          </p>
          <div class="mt-10 flex flex-col gap-4 sm:flex-row">
            <x-cta href="{{ $atlas_access['subscription_url'] }}" text="Suscripcion"
              clases="bg-cde hover:text-morado5 font-semibold text-gray-200" icon="tabler-key-filled" />
            <x-cta href="{{ $atlas_access['account_url'] }}" text="Mi cuenta"
              clases="bg-cde/40 hover:text-morado5 font-semibold text-gray-200" icon="tabler-user-filled" />
          </div>
        @elseif ($state === 'anonymous')
          <p class="text-cde-light text-sm font-semibold uppercase tracking-normal">Inicia sesion</p>
          <h2 class="text-gris1 mt-4 text-4xl font-light">Entra con tu cuenta CDE para abrir Atlas.</h2>
          <p class="text-gris2 mt-6 text-xl font-light leading-relaxed">
            Atlas se abre desde una cuenta del Curso de Desarrollo Espiritual con membresia activa.
          </p>
          <div class="mt-10 flex flex-col gap-4 sm:flex-row">
            <x-cta href="{{ $atlas_access['login_url'] }}" text="Iniciar sesion"
              clases="bg-cde hover:text-morado5 font-semibold text-gray-200" icon="tabler-login-2" />
            <x-cta href="{{ $atlas_access['subscription_url'] }}" text="Suscripcion"
              clases="bg-cde/40 hover:text-morado5 font-semibold text-gray-200" icon="tabler-key-filled" />
          </div>
        @else
          <p class="text-cde-light text-sm font-semibold uppercase tracking-normal">Acceso no disponible</p>
          <h2 class="text-gris1 mt-4 text-4xl font-light">No se puede emitir ahora la entrada a Atlas.</h2>
          <p class="text-gris2 mt-6 text-xl font-light leading-relaxed">
            El acceso de tu cuenta no se ha modificado. Vuelve a intentarlo mas tarde o revisa tu cuenta CDE.
          </p>
          <div class="mt-10 flex flex-col gap-4 sm:flex-row">
            <x-cta href="{{ $atlas_access['account_url'] }}" text="Mi cuenta"
              clases="bg-cde hover:text-morado5 font-semibold text-gray-200" icon="tabler-user-filled" />
          </div>
        @endif
      </section>
    </div>
  @endwhile
@endsection
