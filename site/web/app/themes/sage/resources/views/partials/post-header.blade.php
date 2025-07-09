<header class="post-header flex w-full justify-center px-6 text-center">
    <div class="prose prose-sutil py-24">
        @if (is_tax('revelador'))
            <div class="text-gris3 border-gris3 bg-negro/80 mb-6 inline-block border-y px-4 py-2 text-2xl italic">
                Revelador
            </div>
        @endif

        @if (get_post_type() === 'area')
            <div class="text-gris3 border-gris3 bg-negro/80 mb-6 inline-block border-y px-4 py-2 text-2xl italic">
                Area
            </div>
        @endif
        <h1 class="text-center text-5xl font-thin md:text-7xl">{!! $title !!}</h1>

        @if (is_singular('cde') && is_user_logged_in())
            <button
                class="{{ $is_completed ? 'bg-sol text-gris5 completed' : 'bg-morado3 text-gris1 uncompleted' }} mt-6 inline-flex cursor-pointer items-center rounded px-4 py-2 font-sans text-base transition"
                id="mark-complete" data-post-id="{{ get_the_ID() }}">
                {{ $is_completed ? 'Vista' : 'Marcar como vista' }}
            </button>
        @endif
    </div>
</header>
