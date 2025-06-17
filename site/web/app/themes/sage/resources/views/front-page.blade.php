@extends('layouts.app')

@section('content')
    <div id="cosmos" class="hidden lg:block"></div>
    <div
        class="hidden lg:flex loading-label absolute top-0 left-0 justify-center w-full h-screen items-center p-6 items-center">
        <div
            class="top-1/2 mx-auto md:text-3xl text-2xl p-6 bg-negro/80 border border-blanco rounded text-center relative top-32 md:top-40 font-thin">
            Construyendo Sistema Sutil
        </div>
    </div>

    <div id="solapa"
        class="solapa absolute left-0 top-40 bg-negro/80 p-10 border border-blanco/50 ml-12 transition-opacity opacity-0 duration-1000">
        <div id="epig" class="text-gris2">uno</div>
        <div id="nomb" class="text-3xl"></div>
    </div>

    <section class="px-6 md:px-0 py-12 w-full border-t">
        <div
            class="page-content max-w-4xl mx-auto prose prose-sutil prose-xl md:prose-2xl !leading-tight prose-h2:font-thin prose-h2:leading-tight prose-a:font-thint">
            @php(the_content())</div>
    </section>


    <section class="px-6 w-full mb-12 border-t">
        <h2 class="font-sans py-6 text-center">Últimas noticias publicadas</h2>
        <div class="noticias flex flex-wrap justify-center w-full">
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

    <section class="px-6 border-t mb-12">
        <h2 class="font-sans py-6 text-center">Últimos vídeos publicados</h2>
        <div id="ultimos-videos" class="flex flex-wrap"></div>
    </section>
@endsection
