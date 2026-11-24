@php($isLesson = is_singular('cde'))
<header class="post-header flex w-full justify-center text-center">
  <div class="{{ $isLesson ? 'w-full' : 'w-full pb-4 md:pb-24' }}">
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
        class="cde-breadcrumb {{ $isLesson ? 'bg-negro/80 border-gris3 px-4 py-2 md:px-6' : 'bg-negro/80 mb-6 border-y px-6 py-4' }} flex w-full text-left font-sans text-sm/4 font-light uppercase tracking-wide">
        <ol class="flex w-full flex-wrap items-baseline gap-x-1 gap-y-1">
          @foreach ($cde_breadcrumb as $index => $crumb)
            <li class="{{ $loop->last ? 'current' : '' }} flex gap-2">
              @if ($index > 0)
                <span aria-hidden="true" class="text-gris2">&gt;</span>
              @endif
              @if (!empty($crumb['url']))
                <a href="{{ $crumb['url'] }}" class="text-morado2 hover:text-blanco focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">{{ $crumb['label'] }}</a>
              @else
                <span class="{{ $loop->last ? 'text-blanco' : 'text-gris2' }}">{{ $crumb['label'] }}</span>
              @endif
            </li>
          @endforeach
        </ol>
      </nav>
    @endif

    @if ($isLesson)
      @include('partials.lesson-metadata', ['metadata' => $lesson_metadata ?? []])
      <div class="flex min-h-40 items-center justify-center px-4 py-10 text-white md:min-h-56.75 md:px-10">
        <h1 class="post-header__title m-0 max-w-6xl wrap-anywhere text-center text-4xl leading-tight font-medium text-balance md:text-[3.5rem]">{!! $title !!}</h1>
      </div>
    @else
      <h1 class="post-header__title px-4 text-center text-3xl font-thin md:mb-9 md:mt-24 md:text-6xl">{!! $title !!}</h1>
    @endif
  </div>
</header>
