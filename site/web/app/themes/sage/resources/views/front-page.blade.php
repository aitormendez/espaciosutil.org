@extends('layouts.app')

@section('content')
    <div id="cosmos" class="hidden lg:block"></div>
    <div class="loading-label absolute left-0 top-0 hidden h-screen w-full items-center justify-center p-6 lg:flex">
        <div
            class="bg-negro/80 border-blanco relative top-32 mx-auto rounded border p-6 text-center text-2xl font-thin md:top-40 md:text-3xl">
            Construyendo Sistema Sutil
        </div>
    </div>

    <div id="solapa"
        class="solapa bg-negro/80 border-blanco/50 absolute left-0 top-40 z-[9999999] ml-12 border p-10 opacity-0 transition-opacity duration-1000">
        <div id="epig" class="text-gris2"></div>
        <div id="nomb" class="text-3xl"></div>
    </div>

    <section class="w-full border-t px-6 py-12 md:px-0">
        <div
            class="page-content prose prose-sutil prose-xl md:prose-2xl prose-h2:font-thin prose-h2:leading-tight prose-a:font-thint mx-auto max-w-4xl !leading-tight">
            @php(the_content())</div>
    </section>


    <section class="mb-12 w-full border-t px-6">
        <h2 class="py-6 text-center font-sans">Últimas noticias publicadas</h2>
        <div class="noticias flex w-full flex-wrap justify-center">
            @query([
                'post_type' => 'noticia',
                'posts_per_page' => 4,
                'orderby' => 'date',
                'order' => 'DESC',
            ])

            @posts
                <div class="noticia w-full sm:w-1/2 md:w-1/3 lg:w-1/4">
                    <a href="@permalink" class="hover:text-morado3 mb-4 block">
                        <h2 class="entry-title text-2xl">@title</h2>
                    </a>
                    @thumbnail('large')
                </div>
            @endposts
        </div>
    </section>

    <section class="mb-12 border-t px-6">
        <h2 class="py-6 text-center font-sans">Últimos vídeos publicados</h2>
        <div id="ultimos-videos" class="flex flex-wrap"></div>
    </section>
@endsection
