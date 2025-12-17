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
            <div id="featured-lesson-media" data-media-props='@json($media_props, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)'
                class="featured-media-container flex w-full justify-center p-6">
            </div>
        @endif

        <div class="prose prose-xl bg-morado5/90 prose-sutil md:prose-2xl w-full p-6 md:px-0">
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
            <section
                class="bg-morado4/90 not-prose mx-auto my-10 w-full max-w-4xl rounded-sm px-6 py-6 font-sans text-white"
                id="lesson-quiz" data-quiz-props='@json($quiz_props, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)'>
                <header class="mb-4 text-center">
                    <p class="font-display text-morado1 text-sm uppercase tracking-[0.2em]">Cuestionario</p>
                    <h2 class="font-display text-2xl font-semibold leading-tight text-white">Refuerza lo aprendido</h2>
                    <p class="text-morado1 mt-1 text-base">Responde las preguntas. Puede haber varias opciones
                        correctas.</p>
                    <div class="bg-sol mt-6 inline-block h-[80px] w-[80px] rounded-full p-4 shadow-[0_0_20px_rgb(255_255_255_/0.5)]"
                        data-quiz-counter>
                        <div class="text-2xl font-bold leading-none text-red-500" data-quiz-counter-current>1</div>
                        <div class="text-shadow-[0_0_15px_rgb(255_0_0_/_1)]">de
                            <span data-quiz-counter-total>3</span>
                        </div>
                    </div>
                </header>
                <div data-quiz-target class="text-morado1">
                    <div class="quiz-shell">
                        <div class="quiz-progress" aria-hidden="true">
                            <div class="quiz-progress-bar question" style="--percent: 0%"></div>
                        </div>
                        <div class="quiz-swiper swiper" role="region" aria-label="Cuestionario">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide quiz-slide">
                                    <p class="text-sm">Cargando cuestionario…</p>
                                </div>
                            </div>
                            <div class="quiz-pagination swiper-pagination"></div>
                        </div>
                        <div
                            class="quiz-footer grid grid-cols-1 grid-rows-[auto_auto_auto] items-center gap-3 md:grid-cols-[1fr_auto_1fr] md:grid-rows-1">
                            <div
                                class="flex items-center gap-2 justify-self-center col-start-1 row-start-2 md:justify-self-start md:col-start-1 md:row-start-1">
                                <button type="button"
                                    class="quiz-prev rounded-sm border border-white/20 px-3 py-2 text-sm text-white hover:border-white/40 focus:outline-none focus:ring-2 focus:ring-white/20">
                                    ←
                                </button>
                                <button type="button"
                                    class="quiz-next rounded-sm border border-white/20 px-3 py-2 text-sm text-white hover:border-white/40 focus:outline-none focus:ring-2 focus:ring-white/20">
                                    →
                                </button>
                            </div>
                            <button type="button"
                                class="quiz-validate-next text-morado5 hover:bg-sol justify-self-center col-start-1 row-start-1 md:col-start-2 md:row-start-1 rounded-full bg-green-500 px-4 py-3 text-sm font-semibold transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-white/40">
                                Validar y pasar a la siguiente
                            </button>
                            <div
                                class="flex items-center gap-3 justify-self-center col-start-1 row-start-3 md:justify-self-end md:col-start-3 md:row-start-1">
                                <button type="button"
                                    class="quiz-submit bg-morado1 text-morado5 hover:bg-morado2 rounded-sm px-4 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-white/40">
                                    Finalizar
                                </button>
                                <button type="button"
                                    class="quiz-restart rounded-sm border border-white/20 px-3 py-2 text-sm text-white hover:border-white/40 focus:outline-none focus:ring-2 focus:ring-white/20">
                                    Reiniciar
                                </button>
                            </div>
                        </div>
                    </div>
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
