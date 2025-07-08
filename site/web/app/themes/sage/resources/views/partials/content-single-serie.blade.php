@php $enlaces = get_field('serie_enlaces') @endphp

<article
    @php(post_class('h-entry prose prose-sutil w-full max-w-none'))>
    <header class="w-full text-center flex justify-center px-6 max-w-4xl mx-auto flex-col items-center">
        <h1 class="p-name pt-24 font-thin text-5xl md:text-7xl text-center mb-0">
            {!! $title !!}
        </h1>

        @if ($revelador['rev'])
            <a href="{{ $revelador['rev_link'] }}"
            class="block font-light italic text-2xl border-t border-b pb-3 pt-1 mb-2 mt-10 bg-morado5 px-6">Revelador: {{ $revelador['rev'][0]->name }}</a>
        @endif

        @if ($autor['aut'])
            <a href="{{ $autor['aut_link'] }}"
            class="block font-light italic text-2xl border-t border-b pb-3 pt-1 mb-2 mt-10 bg-morado5 px-6">Autor: {{ $autor['aut'][0]->name }}</a>
        @endif

        <img src="{{ $thumb['url'] }}" alt="{{ $thumb['alt'] }}" srcset="{{ $thumb['srcset'] }}"
            sizes="(min-width: 768px) 600px, 80vw" class="w-80 rounded-full aspect-square mb-20">
    </header>

    <div class="relative border-t border-blanco flex justify-center pb-20 text-blanco flex-wrap bg-morado5/90">
        <div class="w-full border-b border-blanco relative">
            <ul class="pl-0 list-none font-sans font-light not-prose max-w-4xl mx-auto w-full">
                @foreach ($enlaces as $enlace)
                    <li class="border-t border-gris4 py-2 text-xl first:border-t-0">
                        @if ($enlace['serie_formato'] == 'video')
                            Ver <a href="{{ $enlace['serie_enlace'] }}" target="_blank"
                                class="text-morado2">{!! $title !!}</a> en YouTube
                        @elseif($enlace['serie_formato'] == 'audio')
                            Escuchar <a href="{{ $enlace['serie_enlace'] }}"  target="_blank"
                                class="text-morado2">{!! $title !!}</a> en Ivoox
                        @endif
    </li>
    @endforeach
    </ul>
    </div>

    <div class="relative px-6 max-w-4xl mx-auto text-3xl">
        @php(the_content())
    </div>
    </div>
</article>
