    @php
      $navContextData = nav_context_data();
      $is_pmpro_page = $navContextData['is_pmpro_page'];
      $show_cde_hero_nav = $navContextData['show_cde_hero_nav'];
      $is_cde_context = $navContextData['nav_context'] === 'cde';
      $variant = $variant ?? null;
      $is_membership_landing = $variant === 'membership-landing';
      $show_membership_tabs = $is_pmpro_page && !$is_membership_landing;
      $hero_excerpt = $is_membership_landing && has_excerpt() ? get_the_excerpt() : '';
      $decoded_title = wp_specialchars_decode((string) $title, ENT_QUOTES);
      $safe_title_with_break = wp_kses(str_ireplace(['<br/>', '<br />'], '<br>', $decoded_title), ['br' => []]);
      $use_compact_header = $is_pmpro_page || ($is_cde_context && !$show_cde_hero_nav);
    @endphp

    <div class="page-header flex w-full flex-col items-center px-6 text-center">
      @if (!$show_cde_hero_nav)
        @if ($is_membership_landing)
          <div class="mb-14 w-full max-w-5xl pt-24 lg:mb-24 lg:pt-28">
            <div class="mx-auto flex w-full max-w-4xl flex-col items-center text-left font-sans">
              <h1 class="mb-10 text-center text-5xl font-thin lg:text-5xl">
                {!! $safe_title_with_break !!}
              </h1>

              @if (!empty($hero_excerpt))
                <div class="text-center text-xl font-thin">
                  {!! wp_kses_post(wpautop($hero_excerpt)) !!}
                </div>
              @endif


              <div class="membership-hero-ctas mt-12 flex flex-col gap-4 sm:flex-row">
                <x-cta href="#planes" text="Elegir plan"
                  clases="bg-morado5/90 hover:text-morado5 font-semibold text-gray-200"
                  icon="tabler-arrow-badge-down-filled" />
                <x-cta href="{{ home_url('/leccion-gratuita/') }}" text="Ver lección gratuita"
                  icon="tabler-arrow-badge-right-filled"
                  clases="bg-morado5/30 hover:text-morado5 font-semibold text-gray-200" />
              </div>
            </div>
          </div>
        @else
          <div class="{{ $use_compact_header ? 'font-sans' : 'prose' }} mb-24 w-full max-w-5xl lg:pt-24">
            @if (is_tax('revelador'))
              <div class="text-gris3 border-gris3 bg-negro/80 mb-6 inline-block border-y px-4 py-2 text-2xl italic">
                Revelador
              </div>
            @elseif (is_tax('canal'))
              <div class="text-gris3 border-gris3 bg-negro/80 mb-6 inline-block border-y px-4 py-2 text-2xl italic">
                Canal
              </div>
            @elseif (is_tax('facilitador'))
              <div class="text-gris3 border-gris3 bg-negro/80 mb-6 inline-block border-y px-4 py-2 text-2xl italic">
                Facilitador
              </div>
            @endif
            <h1 class="text-center text-5xl font-thin lg:text-5xl">{!! $safe_title_with_break !!}</h1>
          </div>
        @endif
      @endif


      @if ($show_membership_tabs)
        <nav class="mb-6 hidden w-full justify-center font-sans text-2xl xl:flex">
          <ul class="flex flex-wrap gap-12">
            <x-navigation name="membresia_navigation"
              class="membresia-tabs flex flex-wrap justify-center gap-x-4 text-lg font-light lg:gap-x-8"
              id="nav-membresia-desktop" />
          </ul>
        </nav>
      @endif

      @if ($show_cde_hero_nav)
        <div id="arbol" class="w-25 lg:w-50 relative z-10 order-1">
          <x-es-cde-arbol-peq class="relative top-[70px] lg:hidden" />
          <x-es-cde-arbol-grande class="lg:-mb-30 relative top-[40px] hidden lg:block" />
        </div>

        <div
          class="mb-2 flex w-full justify-center pt-6 font-sans font-extralight uppercase tracking-wider lg:text-3xl">
          <div class="text-center">
            <span class="block lg:inline">Curso</span>
            <span class="block lg:inline"> de desarrollo</span>
            <span class="block lg:inline">espiritual</span>
          </div>
        </div>
      @endif
    </div>
