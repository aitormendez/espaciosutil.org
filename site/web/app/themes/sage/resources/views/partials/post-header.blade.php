<header class="post-header flex w-full justify-center text-center">
  <div class="w-full pb-4 md:pb-24">
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
        class="bg-negro/80 mb-6 flex w-full border-y px-6 py-4 text-left font-sans text-sm/4 font-light uppercase tracking-wide">
        <ol class="flex w-full flex-col items-start gap-2 md:flex-row md:flex-wrap md:gap-1">
          @foreach ($cde_breadcrumb as $index => $crumb)
            <li class="{{ $loop->last ? 'current' : '' }} flex gap-2">
              @if ($index > 0)
                <span class="text-gris3">&gt;</span>
              @endif
              @if (!empty($crumb['url']))
                <a href="{{ $crumb['url'] }}" class="text-morado2 hover:text-blanco">{{ $crumb['label'] }}</a>
              @else
                <span class="{{ $loop->last ? 'text-cde-light' : 'text-gris3' }}">{{ $crumb['label'] }}</span>
              @endif
            </li>
          @endforeach
        </ol>
      </nav>
    @endif

    <h1 class="px-4 text-center text-3xl font-thin md:mb-9 md:mt-24 md:text-6xl">{!! $title !!}</h1>

    @if (is_singular('cde') && is_user_logged_in())
      <button
        class="{{ $is_completed ? 'completed text-sol' : 'text-gris2' }} mt-6 cursor-pointer px-4 py-2 font-sans text-base transition-colors"
        id="mark-complete" data-post-id="{{ get_the_ID() }}">
        <div class="flex flex-col items-center">
          <div class="btn-text">{{ $is_completed ? 'Vista' : 'Marcar como vista' }}</div>
          <x-coolicon-show class="icon-show {{ $is_completed ? '' : 'hidden' }} block h-8 w-8" />
          <x-coolicon-hide class="icon-hide {{ $is_completed ? 'hidden' : '' }} block h-8 w-8" />
        </div>
      </button>
    @endif
  </div>
</header>
