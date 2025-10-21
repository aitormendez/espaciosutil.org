<header class="post-header flex w-full justify-center text-center">
    <div class="prose w-full pb-24">
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

        @if (!empty($cde_breadcrumb))
            <nav aria-label="Miga de pan"
                class="not-prose bg-negro/80 leading-2 mb-6 flex w-full border-y px-6 py-4 font-sans text-sm font-light uppercase tracking-wide">
                <ol class="flex w-full flex-col items-start gap-2 md:flex-row md:flex-wrap md:items-center md:gap-3">
                    @foreach ($cde_breadcrumb as $index => $crumb)
                        <li class="flex items-center gap-2">
                            @if ($index > 0)
                                <span class="text-gris3">&gt;</span>
                            @endif
                            @if (!empty($crumb['url']))
                                <a href="{{ $crumb['url'] }}"
                                    class="text-morado2 hover:text-blanco">{{ $crumb['label'] }}</a>
                            @else
                                <span class="text-gris3">{{ $crumb['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
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
