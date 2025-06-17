<div class="page-header w-full text-center flex justify-center px-6">
    <div class="prose max-w-5xl prose-sutil mb-24 md:pt-24">
        @if (is_tax('revelador'))
            <div class="inline-block italic  py-2 px-4 text-gris3 border-y border-gris3 text-2xl bg-negro/80 mb-6">
                Revelador
            </div>
        @elseif (is_tax('canal'))
            <div class="inline-block italic  py-2 px-4 text-gris3 border-y border-gris3 text-2xl bg-negro/80 mb-6">
                Canal
            </div>
        @elseif (is_tax('facilitador'))
            <div class="inline-block italic  py-2 px-4 text-gris3 border-y border-gris3 text-2xl bg-negro/80 mb-6">
                Facilitador
            </div>
        @endif
        <h1 class="font-thin text-5xl md:text-7xl text-center">{!! $title !!}</h1>
    </div>
</div>
