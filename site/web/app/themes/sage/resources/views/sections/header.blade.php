<?php use Illuminate\Support\Facades\Vite; ?>

@php
    $navContextData = nav_context_data();
    $isCdeContext = $navContextData['nav_context'] === 'cde';
    $brandUrl = home_url('/');
    $cdeHubUrl = home_url('/curso-de-desarrollo-espiritual/');
@endphp

<header id="banner" class="absolute z-40 w-full text-white xl:fixed">
    <div class="bg-negro absolute left-0 top-0 hidden h-full w-full opacity-80 md:block"></div>

    <div id="brand" class="flex flex-nowrap items-center">
        <a class="brand-link relative inline-flex flex-col items-center p-6 font-bold md:flex-row"
            href="{{ $brandUrl }}" @if (should_prevent_barba_for_url($brandUrl)) data-barba-prevent @endif>
            <div id="simbolo" class="my-6 w-24 md:my-0 md:mr-8 md:w-12">
                <img src="{{ Vite::asset('resources/images/simbolo-espacio-sutil-color.svg') }}"
                    alt="Símbolo Espacio Sutil">
            </div>
            <div id="logotipo">
                <x-es-logotipo-espacio-sutil class="h-auto w-[212px]" />
            </div>
        </a>

        @if ($isCdeContext)
            <a id="cde" class="cde-link relative top-[-8px] mt-4 hidden items-center gap-2 lg:inline-flex"
                href="{{ $cdeHubUrl }}" @if (should_prevent_barba_for_url($cdeHubUrl)) data-barba-prevent @endif>
                <x-es-cde-arbol-peq class="absolute w-12" />
                <span
                    class="relative left-[55px] top-[3px] hidden font-sans text-[1.8rem] font-light tracking-wide lg:block">CDE</span>
            </a>
        @endif
    </div>

    <div id="submenu-bg" class="border-gris3 relative hidden w-screen border-t xl:block"></div>

    <nav id="nav" data-nav-context="{{ $navContextData['nav_context'] }}"
        data-primary-menu="{{ $navContextData['primary_menu_name'] }}"
        class="bg-negro text-blanco {{ is_admin_bar_showing() ? 'xl:top-[57px]' : 'xl:top-[26px]' }} fixed top-0 z-40 min-h-screen w-screen xl:right-6 xl:min-h-0 xl:w-auto xl:bg-transparent">
        <ul class="my-menu flex flex-wrap items-center p-6 text-2xl xl:px-0 xl:pb-[23px] xl:pt-0">
            <x-navigation name="{{ $navContextData['primary_menu_name'] }}" class="flex font-extralight" />

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
