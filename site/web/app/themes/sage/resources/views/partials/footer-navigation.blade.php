@if ($footer_navigation)
  <ul id="nav-footer-legacy" class="mx-auto flex max-w-4xl flex-wrap px-6 lg:px-0 xl:max-w-6xl">
    @foreach ($footer_navigation as $item)
      <li
        class="border-gris1 {{ $item->activeAncestor ? 'active-ancestor' : '' }} {{ $item->active ? 'active' : '' }} flex w-full flex-col border-b px-2 py-4 last:border-none md:w-1/2 xl:w-1/3">
        <h2 class="text-gris1 mb-4 text-2xl font-extralight uppercase md:w-[300px]">
          {{ $item->label }}
        </h2>

        @if ($item->children)
          <ul class="my-child-menu">
            @foreach ($item->children as $child)
              <li class="border-gris4 border-b py-2 last:border-none">
                <a class="text-gris2 hover:text-blanco {{ $child->classes ?? '' }} {{ $child->active ? 'active' : '' }} font-extralight"
                  href="{{ $child->url }}" @if (should_prevent_barba_for_url($child->url)) data-barba-prevent @endif>
                  {{ $child->label }}
                </a>
              </li>
            @endforeach
          </ul>
        @endif
      </li>
    @endforeach
  </ul>
@endif
