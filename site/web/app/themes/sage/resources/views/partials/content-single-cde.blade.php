<article @php post_class('h-entry') @endphp>
    <header>
        @include('partials.post-header')
    </header>

    <div class="border-blanco text-blanco bg-morado5/90 relative flex flex-wrap justify-center border-t pb-6">
        <div
            class="contenido prose prose-sutil prose-xl md:prose-2xl mt-18 mx-auto w-full max-w-4xl px-6 !leading-tight md:px-0">
            {{-- Parte 1: Extracto (visible para todos) --}}
            @php the_field('rich_excerpt') @endphp
        </div>
    </div>

    {{-- Parte 2: Contenido completo (visible solo para miembros logueados con membresía activa) --}}
    @if ($has_access)
        @php($featured_video_id = get_field('featured_video_id'))
        @php($featured_video_library_id = get_field('featured_video_library_id'))
        @php($featured_video_name = get_field('featured_video_name'))
        @php($bunny_pull_zone = getenv('BUNNY_PULL_ZONE'))

        @if ($featured_video_id && $featured_video_library_id && $bunny_pull_zone)
            <div id="featured-video-player" data-video-id="{{ $featured_video_id }}"
                data-video-library-id="{{ $featured_video_library_id }}" data-pull-zone="{{ $bunny_pull_zone }}"
                data-video-name="{{ $featured_video_name }}" class="aspect-video w-full p-6">
            </div>
        @endif

        <div class="bg-morado5/90 prose prose-sutil prose-xl md:prose-2xl w-full p-6 md:px-0">
            <div class="prose prose-sutil prose-xl md:prose-2xl mx-auto w-full max-w-4xl px-6 !leading-tight md:px-0">
                @php(the_content())
            </div>
        </div>
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
