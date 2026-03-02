<article @php(post_class('h-entry'))>
    @include('partials.post-header')

    @php $series = get_field('area_series_relacionadas') @endphp
    <div
        class="border-blanco text-blanco bg-morado5/90 max relative flex flex-col flex-wrap justify-center border-t pb-20">
        <div class="contenido prose prose-xl md:prose-2xl mx-auto mt-24 w-full max-w-4xl px-6 !leading-tight md:px-0">
            @php(the_content())
        </div>

        <ul class="series-relacionadas prose prose-2xl mx-auto mt-24 w-full max-w-4xl px-6 md:px-0">
            @forelse ($series as $serie)
                @php $thumb = $loop_thumb_postid($serie) @endphp

                @include('partials.serie-relacionada')

            @empty
                <p class="text-gris3 border-gris3 border-y">Este área no contiene series aún</p>
            @endforelse
        </ul>

    </div>

    @php(comments_template())
</article>
