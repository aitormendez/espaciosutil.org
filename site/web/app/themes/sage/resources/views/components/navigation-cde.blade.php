@props([
    'name' => 'cde_navigation',
])

@php
    $menu = \Log1x\Navi\Navi::make()->build($name);
@endphp

@if ($menu->isNotEmpty())
    <ul id="nav-cde-hero" class="flex flex-col items-start lg:flex-row lg:gap-12">
        @foreach ($menu->all() as $item)
            <li @class([
                '',
                'active-ancestor' => $item->activeAncestor,
                'active' => $item->active,
            ])>
                <a href="{{ $item->url }}" @if (should_prevent_barba_for_url($item->url)) data-barba-prevent @endif @class([
                    'text-morado2' => !$item->active,
                    'text-blanco font-semibold' => $item->active,
                ])>
                    {{ $item->label }}
                </a>
            </li>
        @endforeach
    </ul>
@endif
