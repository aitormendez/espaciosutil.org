@php
    $menu = \Log1x\Navi\Navi::make()->build($name);
    $logoutUrl = htmlspecialchars_decode(wp_logout_url(home_url('/login/')), ENT_QUOTES);
    $isPrimaryNavigation = in_array((string) $name, ['primary_navigation', 'cde_navigation'], true);
@endphp

@if ($menu->isNotEmpty())
    @foreach ($menu->all() as $item)
        @php
            $show = should_render_navigation_item($item);
            $itemClassList = nav_item_classes($item);
            $itemClassNames = implode(' ', $itemClassList);
            $itemIsLogoutLink = in_array('logout-link', $itemClassList, true);
            $itemUrl = $itemIsLogoutLink ? $logoutUrl : $item->url;
            $itemPath = normalize_section_path((string) (wp_parse_url((string) $itemUrl, PHP_URL_PATH) ?? '/'));
            $itemLabel = $isPrimaryNavigation && $itemPath === '/suscripcion/' ? 'Suscripción' : $item->label;
        @endphp

        @if ($show)
            <li
                class="my-menu-item {{ $attributes->get('class') }} {{ $itemClassNames }} {{ $item->activeAncestor ? 'active-ancestor' : '' }} {{ $item->active ? 'active' : '' }} my-2 inline-block w-full xl:w-auto xl:px-6">
                <a class="my-menu-link" href="{{ $itemUrl }}" data-section="section-{{ $item->id }}"
                    @if (should_prevent_barba_for_url($itemUrl)) data-barba-prevent @endif
                    data-color="{{ get_field('menu_item_bg_color', $item->id) }}">
                    {{ $itemLabel }}
                </a>
                @if ($item->children)
                    <ul
                        class="my-child-menu {{ is_admin_bar_showing() ? 'xl:top-40' : 'xl:top-32' }} h-0 overflow-hidden xl:fixed xl:hidden xl:h-auto">
                        @foreach ($item->children as $child)
                            @php
                                $childClassList = nav_item_classes($child);
                                $childClassNames = implode(' ', $childClassList);
                                $childIsLogoutLink = in_array('logout-link', $childClassList, true);
                                $childUrl = $childIsLogoutLink ? $logoutUrl : $child->url;
                            @endphp
                            <li
                                class="text-gris2 xl:text-blanco my-child-item {{ $childClassNames }} {{ $child->active ? 'active' : '' }} border-b border-zinc-800 pb-1 font-thin first:border-t xl:border-none xl:pb-0 xl:opacity-0">
                                <a class="my-child-menu-link" href="{{ $childUrl }}"
                                    @if (should_prevent_barba_for_url($childUrl)) data-barba-prevent @endif>
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
