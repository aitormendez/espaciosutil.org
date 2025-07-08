@props([
    'name' => 'footer_navigation',
])

@php($menu = \Log1x\Navi\Navi::make()->build($name))

@if ($menu->isNotEmpty())
    <ul id="nav" class="mx-auto flex max-w-4xl flex-wrap px-6 lg:px-0 xl:max-w-6xl">
        @foreach ($menu->all() as $item)
            <li @class([
                'px-2 flex flex-col py-4 border-b border-gris1 last:border-none w-full md:w-1/2 xl:w-1/3',
                'active-ancestor' => $item->activeAncestor,
                'active' => $item->active,
            ])>
                <h2 class="text-gris1 mb-4 text-2xl font-extralight uppercase md:w-[300px]">
                    {{ $item->label }}
                </h2>

                @if ($item->children)
                    <ul class="my-child-menu">
                        @foreach ($item->children as $child)
                            <li class="border-gris4 border-b py-2 last:border-none">
                                <a @class([
                                    'font-extralight text-gris2 hover:text-blanco',
                                    $child->classes ?? '',
                                    'active' => $child->active,
                                ]) href="{{ $child->url }}">
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
