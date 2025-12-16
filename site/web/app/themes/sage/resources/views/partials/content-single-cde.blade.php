<article @php post_class('h-entry') @endphp>
    <header>
        @include('partials.post-header')
    </header>

    <div class="border-blanco text-blanco bg-morado5/90 relative flex flex-wrap justify-center border-t pb-6">
        <div class="contenido prose md:prose-2xl mt-18 mx-auto w-full max-w-4xl px-6 !leading-tight md:px-0">
            {{-- Parte 1: Extracto (visible para todos) --}}
            @php the_field('rich_excerpt') @endphp

            @if (!empty($lesson_subindex['items']))
                <nav class="bg-morado4/90 not-prose mt-12 rounded-sm px-6 py-5 font-sans text-base"
                    aria-labelledby="lesson-subindex-title">
                    <p id="lesson-subindex-title" class="font-display text-morado1 font-medium tracking-wide">
                        Subíndice de la lección: {{ $lesson_subindex_root_title }}
                    </p>
                    <div class="mt-4">
                        @include('partials.lesson-subindex', [
                            'items' => $lesson_subindex['items'],
                            'interactive' => $has_access,
                            'is_nested' => false,
                        ])
                    </div>
                </nav>
            @endif
        </div>
    </div>

    {{-- Parte 2: Contenido completo (visible solo para miembros logueados con membresía activa) --}}
    @if ($has_access)
        @php
            $has_featured_media = ($featured_media['has_video'] ?? false) || ($featured_media['has_audio'] ?? false);
            $media_props = [
                'video' => $featured_media['video'] ?? null,
                'audio' => $featured_media['audio'] ?? null,
                'pullZone' => $featured_media['pull_zone'] ?? null,
                'lessonTitle' => get_the_title(),
            ];
        @endphp

        @if ($has_featured_media)
            <div id="featured-lesson-media"
                data-media-props='@json($media_props, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)'
                class="featured-media-container flex w-full justify-center p-6">
            </div>
        @endif

        <div class="bg-morado5/90 prose prose-sutil prose-xl md:prose-2xl w-full p-6 md:px-0">
            <div class="prose prose-sutil prose-xl md:prose-2xl mx-auto w-full max-w-4xl px-6 !leading-tight md:px-0">
                @php the_content() @endphp
            </div>
        </div>

        @if ($lesson_quiz['enabled'] ?? false)
            @php
                $quiz_props = [
                    'postId' => $lesson_quiz['post_id'] ?? null,
                    'questions' => $lesson_quiz['questions'] ?? [],
                ];
            @endphp
            <section class="bg-morado4/90 not-prose mx-auto my-10 w-full max-w-4xl rounded-sm px-6 py-6 text-white"
                id="lesson-quiz"
                data-quiz-props='@json($quiz_props, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)'>
                <header class="mb-4">
                    <p class="font-display text-sm uppercase tracking-[0.2em] text-morado1">Cuestionario</p>
                    <h2 class="font-display text-2xl font-semibold leading-tight text-white">Refuerza lo aprendido</h2>
                    <p class="mt-1 text-base text-morado1">Responde las preguntas. Puede haber varias opciones correctas.</p>
                </header>
                <div data-quiz-target class="text-morado1">
                    <p class="text-sm">Cargando cuestionario…</p>
                </div>
            </section>
        @endif

        @include('partials.videos-realacionados-cde')

        @php(comments_template())
    @else
        <div class="prose prose-sutil prose-xl md:prose-2xl mb-8 w-full !p-6 md:px-0">
            <div class="bg-morado3 mx-auto mt-8 max-w-4xl rounded p-4 text-white">
                <p>Para acceder al contenido completo de esta lección, por favor <a
                        href="{{ wp_login_url(get_permalink()) }}" class="underline">inicia sesión</a> o <a
                        href="{{ home_url('/niveles-de-membresia/') }}" class="underline">adquiere una membresía
                        activa</a>.</p>
            </div>
        </div>

    @endif
</article>
