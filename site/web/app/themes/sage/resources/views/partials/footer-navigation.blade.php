@if ($footer_navigation)
    <ul id="nav" class="flex flex-wrap max-w-4xl xl:max-w-6xl mx-auto px-6 lg:px-0">
        @foreach ($footer_navigation as $item)
            <li
                class="px-2 flex flex-col py-4  border-b border-gris1 last:border-none w-full md:w-1/2 xl:w-1/3 {{ $item->activeAncestor ? 'active-ancestor' : '' }} {{ $item->active ? 'active' : '' }}">
                <h2 class="uppercase mb-4 font-extralight text-gris1 text-2xl md:w-[300px]">
                    {{ $item->label }}
                </h2>

                @if ($item->children)
                    <ul class="my-child-menu">
                        @foreach ($item->children as $child)
                            <li class="border-b border-gris4 last:border-none py-2">
                                <a class="font-extralight text-gris2 hover:text-blanco {{ $child->classes ?? '' }} {{ $child->active ? 'active' : '' }}"
                                    href="{{ $child->url }}">
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
