@php
    $menu = \Log1x\Navi\Navi::make()->build($name);
@endphp

@if ($menu->isNotEmpty())
    @foreach ($menu->all() as $item)
        @php
            $show = should_render_navigation_item($item);
            $itemClassNames = implode(' ', nav_item_classes($item));
        @endphp

        @if ($show)
            <li
                class="my-menu-item {{ $attributes->get('class') }} {{ $itemClassNames }} {{ $item->activeAncestor ? 'active-ancestor' : '' }} {{ $item->active ? 'active' : '' }} my-2 inline-block w-full xl:w-auto xl:px-6">
                <a class="hover:text-morado3 {{ $item->active ? 'text-morado3' : 'text-blanco' }}"
                    href="{{ $item->url }}" data-section="section-{{ $item->id }}"
                    @if (should_prevent_barba_for_url($item->url)) data-barba-prevent @endif
                    data-color="{{ get_field('menu_item_bg_color', $item->id) }}">
                    {{ $item->label }}
                </a>
                @if ($item->children)
                    <ul
                        class="my-child-menu {{ is_admin_bar_showing() ? 'xl:top-48' : 'xl:top-40' }} h-0 overflow-hidden xl:fixed xl:hidden xl:h-auto">
                        @foreach ($item->children as $child)
                            @php
                                $childClassNames = implode(' ', nav_item_classes($child));
                            @endphp
                            <li
                                class="text-gris2 xl:text-blanco my-child-item {{ $childClassNames }} {{ $child->active ? 'active' : '' }} border-b border-zinc-800 pb-1 font-thin first:border-t xl:border-none xl:pb-0 xl:opacity-0">
                                <a class="hover:text-morado3" href="{{ $child->url }}"
                                    @if (should_prevent_barba_for_url($child->url)) data-barba-prevent @endif>
                                    {{ $child->label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endif
    @endforeach
@endif
