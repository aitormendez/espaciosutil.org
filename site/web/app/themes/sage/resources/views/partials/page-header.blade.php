    @php
        $navContextData = nav_context_data();
        $is_pmpro_page = $navContextData['is_pmpro_page'];
        $show_cde_hero_nav = $navContextData['show_cde_hero_nav'];
        $is_cde_context = $navContextData['nav_context'] === 'cde';
        $use_compact_header = $is_pmpro_page || ($is_cde_context && !$show_cde_hero_nav);
    @endphp

    <div class="page-header flex w-full flex-col items-center px-6 text-center">
        @if (!$show_cde_hero_nav)
            <div class="{{ $use_compact_header ? 'font-sans' : 'prose' }} mb-24 w-full max-w-5xl lg:pt-24">
                @if (is_tax('revelador'))
                    <div
                        class="text-gris3 border-gris3 bg-negro/80 mb-6 inline-block border-y px-4 py-2 text-2xl italic">
                        Revelador
                    </div>
                @elseif (is_tax('canal'))
                    <div
                        class="text-gris3 border-gris3 bg-negro/80 mb-6 inline-block border-y px-4 py-2 text-2xl italic">
                        Canal
                    </div>
                @elseif (is_tax('facilitador'))
                    <div
                        class="text-gris3 border-gris3 bg-negro/80 mb-6 inline-block border-y px-4 py-2 text-2xl italic">
                        Facilitador
                    </div>
                @endif
                <h1 class="text-center text-5xl font-thin lg:text-5xl">{!! $title !!}</h1>
            </div>
        @endif


        @if ($is_pmpro_page)
            <nav class="mb-6 hidden w-full justify-center font-sans text-2xl xl:flex">
                <ul class="flex flex-wrap gap-12">
                    <x-navigation name="membresia_navigation"
                        class="membresia-tabs flex flex-wrap justify-center gap-x-4 text-lg font-light lg:gap-x-8"
                        id="nav-membresia-desktop" />
                </ul>
            </nav>
        @endif

        @if ($show_cde_hero_nav)
            <div id="arbol" class="w-50 relative z-10 order-1 lg:order-none">
                <x-es-cde-arbol-peq class="relative top-[120px] lg:hidden" />
                <x-es-cde-arbol-grande class="relative top-[170px] hidden lg:block" />
            </div>

            <div
                class="mb-2 flex w-full justify-between pt-12 font-sans font-extralight uppercase tracking-wider lg:static lg:pt-0">
                <div class="text-left">
                    <span class="block lg:inline">Curso</span>
                    <span class="block lg:inline"> de desarrollo</span>
                    <span class="block lg:inline">espiritual</span>
                </div>
            </div>
        @endif
    </div>
