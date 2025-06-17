@if ($navigation)
    <div id="submenu-bg" class="hidden xl:block w-screen border-t border-gris3 relative"></div>
    <nav id="nav"
        class="z-40 fixed top-0 xl:right-6 xl:top-8 w-screen xl:w-auto min-h-screen xl:min-h-0 bg-negro xl:bg-transparent text-blanco">
        <ul class="my-menu text-2xl flex items-center flex-wrap p-6 xl:px-0 xl:pb-[32px] xl:pt-0">
            @foreach ($navigation as $item)
                <li
                    class="{{ $item->classes }} w-full xl:w-auto my-menu-item inline-block xl:px-6 {{ $item->activeAncestor ? 'active-ancestor' : '' }} {{ $item->active ? 'active' : '' }}">
                    <a class="hover:text-morado3" href="{{ $item->url }}"
                        data-color="{{ get_field('menu_item_bg_color', $item->id) }}">
                        {{ $item->label }}
                    </a>

                    @if ($item->children)
                        <ul
                            class="my-child-menu xl:fixed xl:top-32 xl:hidden h-0 overflow-hidden xl:h-auto my-2 xl:my-0">
                            @foreach ($item->children as $child)
                                <li
                                    class="text-gris2 xl:text-blanco pb-1 xl:pb-0 first:border-t my-child-item xl:opacity-0 font-thin border-b border-zinc-800 xl:border-none {{ $child->classes ?? '' }} {{ $child->active ? 'active' : '' }}">
                                    <a class="hover:text-morado3" href="{{ $child->url }}">
                                        {{ $child->label }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
        <div id="linea" class="hidden xl:block absolute left-0 bottom-0 w-0 h-1 bg-gris3"></div>
    </nav>
@endif
