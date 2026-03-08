<article @php(post_class('relative px-6 border-b border-gris4'))>
  <header>
    <h2 class="entry-title mx-auto w-full max-w-4xl">
      <a href="{{ get_permalink() }}">
        {!! $title !!}
      </a>
    </h2>
  </header>

  <div class="entry-summary mx-auto w-full max-w-4xl">
    @php(the_excerpt())
  </div>
</article>
