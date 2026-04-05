@php
  $guideLesson = $program_guide_lesson ?? null;
  $featuredMedia = $guideLesson['featured_media'] ?? [];
  $hasFeaturedMedia = ($featuredMedia['has_video'] ?? false) || ($featuredMedia['has_audio'] ?? false);
@endphp

@if ($guideLesson && $hasFeaturedMedia)
  @php
    $mediaProps = [
        'video' => $featuredMedia['video'] ?? null,
        'audio' => $featuredMedia['audio'] ?? null,
        'pullZone' => $featuredMedia['pull_zone'] ?? null,
        'lessonTitle' => $guideLesson['title'] ?? 'Presentación del curso',
    ];
    $guideItems = $guideLesson['subindex']['items'] ?? [];
  @endphp

  <div class="mx-auto max-w-6xl px-6 lg:px-0">
    <div class="prose-cde mx-auto max-w-3xl">
      <h2>Vídeo guía del curso</h2>
      <p>
        Esta presentación ofrece una visión de conjunto del CDE: qué estudia, cómo se estructura y de qué manera se
        recorre el programa.
      </p>
      @if (!empty($guideLesson['permalink']))
        <p class="not-prose mt-6">
          <a href="{{ $guideLesson['permalink'] }}"
            class="border-morado1/50 text-morado1 hover:border-morado1 hover:text-blanco inline-flex items-center gap-2 rounded border px-4 py-2 text-sm font-semibold tracking-wide transition">
            Abrir la lección completa
          </a>
        </p>
      @endif
    </div>

    <div class="mt-10 flex justify-center">
      <div class="w-full" data-media-props='@json($mediaProps, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)'>
      </div>
    </div>

    @if (!empty($guideItems))
      <nav class="bg-morado4/90 not-prose mx-auto mt-10 max-w-4xl rounded-sm px-6 py-5 font-sans text-base"
        aria-labelledby="program-guide-subindex-title">
        <p id="program-guide-subindex-title" class="font-display text-morado1 font-medium tracking-wide">
          Capítulos de la presentación
        </p>
        <div class="mt-4">
          @include('partials.lesson-subindex', [
              'items' => $guideItems,
              'interactive' => true,
              'link_titles' => false,
              'enable_seek_buttons' => true,
              'plain_title_classes' => 'text-white/65 tracking-wide font-light',
              'is_nested' => false,
          ])
        </div>
      </nav>
    @endif
  </div>
@endif
