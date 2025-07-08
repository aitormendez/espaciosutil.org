<article @php(post_class('h-entry'))>
    <header>
        @include('partials.post-header')

        @include('partials.entry-meta')
    </header>

    <div class="e-content relative border-t border-blanco flex justify-center pb-20 text-blanco flex-wrap bg-morado5/90">
        <div
            class="contenido max-w-4xl mx-auto w-full prose prose-sutil mt-24 prose-xl md:prose-2xl !leading-tight px-6 md:px-0">
            @php(the_content())
        </div>
    </div>
    <footer>
        {!! wp_link_pages([
            'echo' => 0,
            'before' => '<nav class="page-nav"><p>' . __('Pages:', 'sage'),
            'after' => '</p></nav>',
        ]) !!}
    </footer>

    @php(comments_template())
</article>
