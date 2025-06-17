<article @php(post_class('relative px-6 border-b border-gris4'))>
    <header class="mx-auto max-w-2xl leading-snug">
        <h2 class="entry-title font-thin">
            <a href="{{ get_permalink() }}">
                {!! $title !!}
            </a>
        </h2>
    </header>

    <div class="thumnail mx-auto max-w-2xl">
        @thumbnail('full')
    </div>


    <div class="entry-summary w-full mx-auto max-w-2xl prose-xl md:prose-2xl leading-snug pb-12">
        @php(the_excerpt())
    </div>
</article>
