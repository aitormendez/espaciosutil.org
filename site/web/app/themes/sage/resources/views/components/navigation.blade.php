@props([
    'name' => 'primary_navigation',
])

@php($menu = \Log1x\Navi\Navi::make()->build($name))

@if ($menu->isNotEmpty())
    <div id="submenu-bg" class="border-gris3 relative hidden w-screen border-t xl:block"></div>

    <nav id="nav"
        class="bg-negro text-blanco fixed top-0 z-40 min-h-screen w-screen xl:right-6 xl:top-8 xl:min-h-0 xl:w-auto xl:bg-transparent">
        <ul class="my-menu flex flex-wrap items-center p-6 text-2xl xl:px-0 xl:pb-[32px] xl:pt-0">
            @foreach ($menu->all() as $item)
                <li
                    class="{{ $item->classes }} my-menu-item {{ $item->activeAncestor ? 'active-ancestor' : '' }} {{ $item->active ? 'active' : '' }} inline-block w-full xl:w-auto xl:px-6">
                    <a class="hover:text-morado3" href="{{ $item->url }}"
                        data-color="{{ get_field('menu_item_bg_color', $item->id) }}">
                        {{ $item->label }}
                    </a>

                    @if ($item->children)
                        <ul
                            class="my-child-menu my-2 h-0 overflow-hidden xl:fixed xl:top-32 xl:my-0 xl:hidden xl:h-auto">
                            @foreach ($item->children as $child)
                                <li
                                    class="text-gris2 xl:text-blanco my-child-item {{ $child->classes ?? '' }} {{ $child->active ? 'active' : '' }} border-b border-zinc-800 pb-1 font-thin first:border-t xl:border-none xl:pb-0 xl:opacity-0">
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

        <div id="linea" class="bg-gris3 absolute bottom-0 left-0 hidden h-1 w-0 xl:block"></div>
    </nav>
@endif
