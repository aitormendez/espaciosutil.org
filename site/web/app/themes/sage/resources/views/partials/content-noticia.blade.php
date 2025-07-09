<article @php(post_class('relative px-6 border-b border-gris4'))>
    <header class="mx-auto max-w-2xl leading-snug">
        <h2 class="entry-title font-thin">
            <a href="{{ get_permalink() }}">
                {!! $title !!}
            </a>
        </h2>
    </header>

    <div class="thumnail mx-auto max-w-2xl">
        {!! get_the_post_thumbnail(null, 'full') !!}
    </div>


    <div class="entry-summary prose-xl md:prose-2xl mx-auto w-full max-w-2xl pb-12 leading-snug">
        @php(the_excerpt())
    </div>
</article>
