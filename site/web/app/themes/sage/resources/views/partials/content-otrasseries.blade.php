@php $thumb = $loop_thumb() @endphp

<li @php post_class('relative px-6 pb-12 pt-14 border-b border-gris4') @endphp>
    <div class="max-w-4xl mx-auto w-full">
        <img src="{{ $thumb['url'] }}" alt="{{ $thumb['alt'] }}" srcset="{{ $thumb['srcset'] }}"
            sizes="(min-width: 768px) 400px, 80vw" class="w-40 redondo my-0">
        <header class="colores-hover">
            <h2 class="entry-title mt-10 font-sans font-thin">
                {!! $title !!}
            </h2>
            <a href="@field('serie_enlace', 'url')" target="@field('serie_enlace', 'target')"
                class="block border-t border-b border-blanco font-sans uppercase text-sm py-2 text-center">
                @field('serie_enlace', 'title')
            </a>
        </header>

        <div class="entry-summary">
            @php(the_excerpt())
        </div>
    </div>
</li>
