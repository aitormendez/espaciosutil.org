<article @php(post_class('relative px-6 border-b border-gris4'))>
    <header>
        <h2 class="entry-title max-w-4xl mx-auto w-full">
            <a href="{{ get_permalink() }}">
                {!! $title !!}
            </a>
        </h2>
    </header>

    <div class="entry-summary max-w-4xl mx-auto w-full">
        @php(the_excerpt())
    </div>
</article>
