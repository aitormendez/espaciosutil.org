@foreach ($items as $item)
    @php
        $currentLevel = isset($level) ? $level : 0;
    @endphp
    <ul class="">
        <li class="course-index-item">
            <a href="{{ $item->permalink }}" class="text-lg">
                {{ $item->title }}
            </a>

            @if (!empty($item->children))
                <ul class="course-index-children mt-2 space-y-2 border-l border-gray-200 pl-6">
                    @include('partials.course-index-item', [
                        'items' => $item->children,
                        'level' => $currentLevel + 1,
                    ])
                </ul>
            @endif
        </li>
    </ul>
@endforeach
