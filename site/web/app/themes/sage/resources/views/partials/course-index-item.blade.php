@if (!empty($revelador_name) && ($level ?? 0) === 0)
    <h3 class="!mt-0 mb-4 text-2xl font-semibold">{{ $revelador_name }}</h3>
@endif
@foreach ($items as $item)
    @php
        $currentLevel = isset($level) ? $level : 0;
    @endphp
    <ul class="!mb-0 !pl-0">
        <li class="course-index-item list-none">
            <a href="{{ $item->permalink }}" class="bg-blanco/5 rounded-xs flex justify-between p-2 text-lg">
                {{ $item->title }}
                @if (is_user_logged_in())
                    @if (in_array($item->id, $completed_lessons ?? []))
                        <x-coolicon-show class="icon-show text-sol h-6 w-6" />
                    @else
                        <x-coolicon-hide class="icon-hide text-morado1 h-6 w-6" />
                    @endif
                @endif
            </a>

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
