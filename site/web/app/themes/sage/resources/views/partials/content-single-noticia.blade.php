<article @php(post_class('h-entry'))>
  <header>
    @include('partials.post-header')

    @include('partials.entry-meta')
  </header>

  <div class="e-content border-blanco text-blanco bg-morado5/90 relative flex flex-wrap justify-center border-t pb-20">
    <div class="contenido prose prose-xl md:prose-2xl mx-auto mt-24 w-full max-w-4xl px-6 !leading-tight md:px-0">
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
