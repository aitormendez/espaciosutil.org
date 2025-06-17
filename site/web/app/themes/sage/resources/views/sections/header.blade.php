@php use Illuminate\Support\Facades\Vite @endphp

<header id="banner" class="absolute z-40 w-full text-white xl:fixed">
    <div class="bg-negro absolute left-0 top-0 hidden h-full w-full opacity-80 md:block"></div>

    <a id="brand" class="brand relative flex flex-col items-center p-6 font-bold md:flex-row"
        href="{{ home_url('/') }}">
        <div id="simbolo" class="my-6 w-24 md:my-0 md:mr-8 md:w-12">
            <img src="{{ Vite::asset('resources/images/simbolo-espacio-sutil-color.svg') }}" alt="Símbolo Espacio Sutil">
        </div>
        <div id="logotipo">
            <img src="{{ Vite::asset('resources/images/logotipo-espacio-sutil.svg') }}" alt="Logotipo Espacio Sutil">
        </div>
    </a>

    <x-navigation name="primary_navigation" class="hidden xl:flex" id="nav-principal" />

    <button id="burguer" class="hamburger hamburger--squeeze fixed right-0 top-6 z-50 xl:hidden"
        aria-label="Abrir menú">
        <span class="hamburger-box">
            <span class="hamburger-inner"></span>
        </span>
    </button>
</header>
