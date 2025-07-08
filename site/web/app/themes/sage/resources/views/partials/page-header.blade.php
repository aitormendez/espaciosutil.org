<div class="page-header flex w-full flex-col items-center px-6 text-center">
    <div class="prose prose-sutil mb-24 w-full max-w-5xl md:pt-24">
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
        <h1 class="text-center text-5xl font-thin md:text-7xl">{!! $title !!}</h1>
    </div>

    @php
    $pmp_page_ids = [2236, 2237, 2238, 2241, 2244, 2239];
@endphp

@if (is_page($pmp_page_ids))
    <nav class="mb-6 hidden w-full justify-center font-sans text-2xl xl:flex">
        <ul class="flex flex-wrap gap-12">
            <x-navigation name="membresia_navigation"
                class="membresia-tabs flex flex-wrap justify-center gap-x-4 text-lg font-light md:gap-x-8"
                id="nav-membresia-desktop" />
        </ul>
    </nav>
@endif
</div>
