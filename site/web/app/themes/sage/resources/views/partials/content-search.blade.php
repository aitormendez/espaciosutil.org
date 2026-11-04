<article @php post_class('group'); @endphp>
  <a href="{{ get_permalink() }}"
    @if (should_prevent_barba_for_url(get_permalink())) data-barba-prevent @endif
    class="grid min-h-32 grid-cols-[minmax(0,1fr)_2rem] gap-x-5 gap-y-5 py-8 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-morado2 sm:grid-cols-[10rem_minmax(0,1fr)_2rem] sm:gap-x-8 sm:py-10">
    <div class="col-span-1 flex flex-wrap content-start items-start gap-2 font-sans text-xs sm:flex-col sm:gap-2">
      <span class="border-morado2 text-morado1 inline-flex min-h-7 items-center rounded-sm border px-2.5 py-1 font-medium tracking-[0.08em] uppercase">
        {{ $result_context }}
      </span>
      <span class="text-gris1 inline-flex min-h-7 items-center py-1 tracking-[0.08em] uppercase">
        {{ $result_type }}
      </span>
    </div>

    <div class="col-span-2 min-w-0 sm:col-span-1">
      @if ($result_parent !== '')
        <p class="text-gris2 mb-3 font-sans text-sm leading-snug">
          {{ $result_parent }}
        </p>
      @endif

      <h2 class="text-blanco text-2xl leading-tight font-light text-pretty transition-colors group-hover:text-morado1 sm:text-3xl motion-reduce:transition-none">
        {!! $title !!}
      </h2>

      @if ($result_excerpt !== '')
        <p class="text-gris1 mt-4 max-w-3xl font-sans text-base leading-relaxed font-light">
          {{ $result_excerpt }}
        </p>
      @endif
    </div>

    <span aria-hidden="true"
      class="text-morado2 col-start-2 row-start-1 flex h-8 w-8 items-center justify-end transition-transform group-hover:translate-x-1 sm:col-start-3 sm:row-span-2 sm:items-center motion-reduce:transform-none motion-reduce:transition-none">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
        <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    </span>
  </a>
</article>
