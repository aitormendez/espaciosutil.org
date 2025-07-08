<article @php(post_class('h-entry'))>
    @include('partials.post-header')

    @php $series = get_field('area_series_relacionadas') @endphp
    <div
        class="relative border-t border-blanco flex flex-col justify-center pb-20 text-blanco flex-wrap bg-morado5/90 max">
        <div
            class="contenido max-w-4xl mx-auto w-full prose prose-sutil mt-24 prose-xl md:prose-2xl !leading-tight px-6 md:px-0">
            @php(the_content())
        </div>

        <ul class="series-relacionadas prose prose-sutil mt-24 prose-2xl px-6 md:px-0 max-w-4xl mx-auto w-full">
            @forelse ($series as $serie)
                @php $thumb = $loop_thumb_postid($serie) @endphp

                @include('partials.serie-relacionada')

            @empty
                <p class="text-gris3 border-y border-gris3">Este área no contiene series aún</p>
            @endforelse
        </ul>

    </div>

    @php(comments_template())
</article>
