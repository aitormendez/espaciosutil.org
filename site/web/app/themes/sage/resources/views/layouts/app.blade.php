<!doctype html>
<html {!! get_language_attributes() !!}>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        do_action('get_header');
        wp_head();
    @endphp

    <script>
        window.jsData = @json(js_data());
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
    $navContextData = nav_context_data();
    $sectionContext = current_navigation_section_context();
@endphp

<body class="{{ implode(' ', get_body_class()) }}" data-barba="wrapper" data-nav-context="{{ $navContextData['nav_context'] }}"
    data-primary-menu="{{ $navContextData['primary_menu_name'] }}" data-section="{{ $sectionContext['key'] }}"
    data-section-color="{{ $sectionContext['color'] }}">
    @php
        wp_body_open();
    @endphp

    <div id="tsparticles" class="bg-negro fixed top-0 h-full w-full"></div>

    @include('sections.header')

    <div class="t-0 relative z-10" data-barba="container" data-barba-namespace="{{ $barba_namespace }}">
        <main id="main"
            class="main {{ is_front_page() ? 'pt-[256px] lg:pt-0' : 'pt-[350px] lg:pt-[129px]' }} text-blanco w-full">
            @yield('content')
        </main>

        @hasSection('sidebar')
            <aside class="sidebar">
                @yield('sidebar')
            </aside>
        @endif
    </div>

    @include('sections.footer')

    @php
        do_action('get_footer');
        wp_footer();
    @endphp
</body>

</html>
