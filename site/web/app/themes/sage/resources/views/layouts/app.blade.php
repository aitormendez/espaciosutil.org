<!doctype html>
<html @php(language_attributes())>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php(do_action('get_header'))
    @php(wp_head())

    <script>
        window.jsData = @json(js_data());
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body @php(body_class()) data-barba="wrapper">
    @php(wp_body_open())

    <div id="tsparticles" class="bg-negro fixed h-full w-full"></div>

    @include('sections.header')

    <div class="t-0 relative z-10" data-barba="container" data-barba-namespace="{{ $barba_namespace }}">
        <main id="main"
            class="main {{ is_front_page() ? 'pt-[256px] lg:pt-0' : 'pt-[256px] lg:pt-[98px]' }} text-blanco w-full">
            @yield('content')
        </main>

        @hasSection('sidebar')
            <aside class="sidebar">
                @yield('sidebar')
            </aside>
        @endif
    </div>

    @include('sections.footer')

    @php(do_action('get_footer'))
    @php(wp_footer())
</body>

</html>
