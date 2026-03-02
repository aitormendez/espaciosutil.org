<?php use Illuminate\Support\Facades\Vite; ?>

@php
    $navContextData = nav_context_data();
@endphp

<header id="banner" class="absolute z-40 w-full text-white xl:fixed">
    <div class="bg-negro absolute left-0 top-0 hidden h-full w-full opacity-80 md:block"></div>
    @include('partials.top-bar')


    <a id="brand" class="brand relative flex flex-col items-center p-6 font-bold md:flex-row"
        href="{{ home_url('/') }}" @if (should_prevent_barba_for_url(home_url('/'))) data-barba-prevent @endif>
        <div id="simbolo" class="my-6 w-24 md:my-0 md:mr-8 md:w-12">
            <img src="{{ Vite::asset('resources/images/simbolo-espacio-sutil-color.svg') }}" alt="Símbolo Espacio Sutil">
        </div>
        <div id="logotipo">
            <img src="{{ Vite::asset('resources/images/logotipo-espacio-sutil.svg') }}" alt="Logotipo Espacio Sutil">
        </div>
    </a>

    <div id="submenu-bg" class="border-gris3 relative hidden w-screen border-t xl:block"></div>

    <nav id="nav"
        data-nav-context="{{ $navContextData['nav_context'] }}"
        data-primary-menu="{{ $navContextData['primary_menu_name'] }}"
        class="bg-negro text-blanco {{ is_admin_bar_showing() ? 'xl:top-[88px]' : 'xl:top-[57px]' }} fixed top-0 z-40 min-h-screen w-screen xl:right-6 xl:min-h-0 xl:w-auto xl:bg-transparent">
        <ul class="my-menu flex flex-wrap items-center p-6 text-2xl xl:px-0 xl:pb-[25px] xl:pt-0">
            <x-navigation name="primary_navigation" class="flex font-extralight" />

            @if ($navContextData['is_pmpro_page'])
                <x-navigation name="membresia_navigation" class="flex xl:hidden" />
            @endif
        </ul>
        <div id="linea" class="bg-gris3 absolute bottom-0 left-0 hidden h-1 w-0 xl:block"></div>
    </nav>

    <button id="burguer" class="hamburger hamburger--squeeze fixed right-0 top-6 z-50 xl:!hidden"
        aria-label="Abrir menú">
        <span class="hamburger-box">
            <span class="hamburger-inner"></span>
        </span>
    </button>
</header>
