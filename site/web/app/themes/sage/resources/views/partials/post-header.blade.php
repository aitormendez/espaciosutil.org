<header class="post-header w-full text-center flex justify-center px-6">
    <div class="prose prose-sutil py-24">
        @if (is_tax('revelador'))
            <div class="inline-block italic py-2 px-4 text-gris3 border-y border-gris3 text-2xl bg-negro/80 mb-6">
                Revelador
            </div>
        @endif

        @if (get_post_type() === 'area')
            <div class="inline-block italic py-2 px-4 text-gris3 border-y border-gris3 text-2xl bg-negro/80 mb-6">
                Area
            </div>
        @endif
        <h1 class="font-thin text-5xl md:text-7xl text-center">{!! $title !!}</h1>
    </div>
</header>
