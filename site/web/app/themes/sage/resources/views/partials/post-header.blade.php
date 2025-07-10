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
                class="border-gris3 {{ $is_completed ? 'bg-sol' : 'bg-morado2' }} hover:bg-blanco text-gris5 mt-6 cursor-pointer rounded border px-4 py-2 font-sans text-base transition"
                id="mark-complete" data-post-id="{{ get_the_ID() }}">
                <div class="flex flex-col items-center">
                    <div class="btn-text">{{ $is_completed ? 'Vista' : 'Marcar como vista' }}</div>
                    <x-coolicon-show class="icon-show {{ $is_completed ? '' : 'hidden' }} text-gris5 block h-8 w-8" />
                    <x-coolicon-hide class="icon-hide {{ $is_completed ? 'hidden' : '' }} text-morado1 block h-8 w-8" />
                </div>
            </button>
        @endif
    </div>
</header>
