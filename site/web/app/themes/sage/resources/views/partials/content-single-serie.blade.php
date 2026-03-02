@php $enlaces = get_field('serie_enlaces') @endphp

<article @php(post_class('h-entry prose  w-full max-w-none'))>
    <header class="mx-auto flex w-full max-w-4xl flex-col items-center justify-center px-6 text-center">
        <h1 class="p-name mb-0 pt-24 text-center text-5xl font-thin md:text-7xl">
            {!! $title !!}
        </h1>

        @if ($revelador['rev'])
            <a href="{{ $revelador['rev_link'] }}"
                class="bg-morado5 mb-2 mt-10 block border-b border-t px-6 pb-3 pt-1 text-2xl font-light italic">Revelador:
                {{ $revelador['rev'][0]->name }}</a>
        @endif

        @if ($autor['aut'])
            <a href="{{ $autor['aut_link'] }}"
                class="bg-morado5 mb-2 mt-10 block border-b border-t px-6 pb-3 pt-1 text-2xl font-light italic">Autor:
                {{ $autor['aut'][0]->name }}</a>
        @endif

        <img src="{{ $thumb['url'] }}" alt="{{ $thumb['alt'] }}" srcset="{{ $thumb['srcset'] }}"
            sizes="(min-width: 768px) 600px, 80vw" class="mb-20 aspect-square w-80 rounded-full">
    </header>

    <div class="border-blanco text-blanco bg-morado5/90 relative flex flex-wrap justify-center border-t pb-20">
        <div class="border-blanco relative w-full border-b">
            <ul class="not-prose mx-auto w-full max-w-4xl list-none pl-0 font-sans font-light">
                @foreach ($enlaces as $enlace)
                    <li class="border-gris4 border-t py-2 text-xl first:border-t-0">
                        @if ($enlace['serie_formato'] == 'video')
                            Ver <a href="{{ $enlace['serie_enlace'] }}" target="_blank"
                                class="text-morado2">{!! $title !!}</a> en YouTube
                        @elseif($enlace['serie_formato'] == 'audio')
                            Escuchar <a href="{{ $enlace['serie_enlace'] }}" target="_blank"
                                class="text-morado2">{!! $title !!}</a> en Ivoox
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="relative mx-auto max-w-4xl px-6 text-3xl">
            @php(the_content())
        </div>
    </div>
</article>
