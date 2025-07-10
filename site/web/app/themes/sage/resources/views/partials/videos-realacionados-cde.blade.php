@if (!empty($related_lessons))
    <section class="related-lessons bg-morado5/90 py-12 font-sans">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <h2 class="text-gris1 text-2xl tracking-tight sm:text-3xl">Lecciones Relacionadas</h2>
            <div class="mt-8 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 xl:gap-x-8">
                @foreach ($related_lessons as $lesson)
                    <div class="group relative">
                        <a href="{{ $lesson['permalink'] }}">
                            <div class="w-full overflow-hidden rounded-md bg-gray-200 group-hover:opacity-75 lg:h-80">
                                @if ($lesson['poster_url'])
                                    <img src="{{ $lesson['poster_url'] }}"
                                        alt="Poster de la lecciÃ³n: {{ $lesson['title'] }}"
                                        class="h-full w-full object-cover object-center lg:h-full lg:w-full">
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-gray-300">
                                        <span class="text-gray-500">Sin imagen</span>
                                    </div>
                                @endif
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-gris1 text-lg">
                                        <span aria-hidden="true" class="absolute inset-0"></span>
                                        {{ $lesson['title'] }}
                                    </h3>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
