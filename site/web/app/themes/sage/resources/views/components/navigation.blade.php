@php $menu = \Log1x\Navi\Navi::make()->build($name) @endphp

@if ($menu->isNotEmpty())
    @foreach ($menu->all() as $item)
        @php
            $show = true;

            switch ($item->id) {
                case '2262': // 'Cuenta'
                case '2263': // 'Perfil'
                case '2264': // 'Pedidos'
                case '2266': // 'facturacion'
                    $show = is_user_logged_in();
                    break;
                case '2265': // 'Cancelar'
                    $show = is_user_logged_in() && pmpro_hasMembershipLevel();
                    break;
            }
        @endphp

        @if ($show)
            <li
                class="my-menu-item {{ $attributes->get('class') }} {{ $item->activeAncestor ? 'active-ancestor' : '' }} {{ $item->active ? 'active' : '' }} my-2 inline-block w-full xl:w-auto xl:px-6">
                <a class="hover:text-morado3 {{ $item->active ? 'text-morado3' : 'text-gris3' }}"
                    href="{{ $item->url }}" data-color="{{ get_field('menu_item_bg_color', $item->id) }}">
                    {{ $item->label }}
                </a>
                @if ($item->children)
                    <ul class="my-child-menu h-0 overflow-hidden xl:fixed xl:top-40 xl:hidden xl:h-auto">
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
        @endif
    @endforeach
@endif
