<article @php(post_class('h-entry'))>
    <header>
        @include('partials.post-header')
    </header>

    <div class="e-content border-blanco text-blanco bg-morado5/90 relative flex flex-wrap justify-center border-t pb-20">
        <div
            class="contenido prose prose-sutil prose-xl md:prose-2xl mx-auto mt-24 w-full max-w-4xl px-6 !leading-tight md:px-0">
            {{-- Parte 1: Extracto (visible para todos) --}}
            @php(the_field('rich_excerpt'))

            {{-- Parte 2: Contenido completo (visible solo para miembros logueados con membresía activa) --}}
            @if (is_user_logged_in() && function_exists('pmpro_hasMembershipLevel') && pmpro_hasMembershipLevel())
                @php(the_content())
            @else
                <div class="mt-8 rounded bg-yellow-600 p-4 text-white">
                    <p>Para acceder al contenido completo de este curso, por favor <a
                            href="{{ wp_login_url(get_permalink()) }}" class="underline">inicia sesión</a> o <a
                            href="{{ home_url('/niveles-de-membresia/') }}" class="underline">adquiere una membresía
                            activa</a>.</p>
                </div>
            @endif
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
