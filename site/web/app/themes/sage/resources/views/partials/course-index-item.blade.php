@foreach ($items as $item)
    @php
        $currentLevel = isset($level) ? $level : 0;
        $indentClass = 'ml-' . $currentLevel * 4; // Tailwind's ml-4, ml-8, ml-12 etc.
    @endphp
    <ul class="{{ $indentClass }}">
        <li class="course-index-item">
            <a href="{{ $item->permalink }}" class="text-lg">
                {{ $item->title }}
            </a>

            @if (!empty($item->children))
                <ul class="course-index-children {{ $indentClass }} mt-2 space-y-2 border-l border-gray-200 pl-6">
                    @include('partials.course-index-item', [
                        'items' => $item->children,
                        'level' => $currentLevel + 1,
                    ])
                </ul>
            @endif
        </li>
    </ul>
@endforeach
