<article class="bg-morado5/90 text-blanco px-4 py-12 md:px-8">
  <div class="mx-auto max-w-4xl">
    <a class="underline" href="{{ get_post_type_archive_link('practica') }}">Todas las prácticas</a>
    @if (\EspacioSutil\Practicas\Catalog::allowed() || \EspacioSutil\Practicas\Catalog::editor())
      <h1 class="font-display mt-6 text-4xl">{{ get_the_title() }}</h1>
      <div class="prose prose-xl md:prose-2xl mt-8">@php the_content(); @endphp</div>
      @if (!post_password_required())
      @php
        $source = get_post_meta(get_the_ID(), 'referencia_fuente', true);
        $lessons = \EspacioSutil\Practicas\Catalog::lessonsFor(get_the_ID());
      @endphp
      @if ($source)
        <aside class="mt-8 border-t border-white/20 pt-6"><h2 class="font-display text-2xl">Texto de origen</h2><p class="mt-3 whitespace-pre-line">{{ $source }}</p></aside>
      @endif
      <section class="mt-8 border-t border-white/20 pt-6" aria-labelledby="practice-lessons-title">
        <h2 id="practice-lessons-title" class="font-display text-2xl">Lecciones donde aparece esta práctica</h2>
        <ul class="mt-4 space-y-3">
          @forelse ($lessons as $lesson)
            <li><a class="underline" href="{{ get_permalink($lesson) }}">{{ get_the_title($lesson) }}</a></li>
          @empty
            <li>Todavía no hay lecciones disponibles asociadas a esta práctica.</li>
          @endforelse
        </ul>
      </section>
      @endif
    @else
      <h1 class="font-display mt-6 text-4xl">Prácticas</h1>
      @include('partials.practicas-access')
    @endif
  </div>
</article>
