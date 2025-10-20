@foreach ($items as $item)
    @php
        $currentLevel = isset($level) ? $level : 0;
        $isActive = property_exists($item, 'active') ? (bool) $item->active : true;
        $itemClasses = 'bg-blanco/5 rounded-xs flex justify-between p-2 text-lg';
        $inactiveClasses = 'cursor-default text-bg-blanco font-semibold text-lg';
    @endphp
    <ul class="!mb-0 !pl-0">
        <li class="course-index-item list-none">
            @if ($isActive)
                <a href="{{ $item->permalink }}" class="{{ $itemClasses }}">
                    {{ $item->title }}
                    @if (is_user_logged_in())
                        @if (in_array($item->id, $completed_lessons ?? []))
                            <x-coolicon-show class="icon-show text-sol h-6 w-6" />
                        @else
                            <x-coolicon-hide class="icon-hide text-morado1 h-6 w-6" />
                        @endif
                    @endif
                </a>
            @else
                <span class="{{ $inactiveClasses }}">
                    {{ $item->title }}
                </span>
            @endif

            @if (!empty($item->children))
                <ul class="course-index-children !mb-0 mt-2 space-y-2 border-l border-gray-200 pl-6">
                    @include('partials.course-index-item', [
                        'items' => $item->children,
                        'level' => $currentLevel + 1,
                    ])
                </ul>
            @endif
        </li>
    </ul>
@endforeach
