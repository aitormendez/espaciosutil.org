@foreach ($items as $item)
    @php
        $currentLevel = isset($level) ? $level : 0;
        $isActive = property_exists($item, 'active') ? (bool) $item->active : true;
        $rowClasses = 'course-index-row bg-blanco/5 rounded-xs flex items-start gap-2 pl-2 pr-1 py-1 text-lg/[1.4]';
        $linkClasses = 'course-index-link flex-1 text-left';
        $inactiveRowClasses =
            'course-index-inactive bg-blanco/5 rounded-xs flex items-start gap-2 pl-2 pr-1 py-1 text-bg-blanco text-lg/[1.4]';
        $hasChildren = !empty($item->children);
    @endphp
    <ul class="!mb-0 !pl-0">
        <li class="course-index-item list-none" data-has-children="{{ $hasChildren ? 'true' : 'false' }}">
            @php
                $showCompletionIcon = is_user_logged_in();
                $isCompleted = $showCompletionIcon && in_array($item->id, $completed_lessons ?? []);
            @endphp

            @if ($isActive)
                <div class="{{ $rowClasses }}">
                    <a href="{{ $item->permalink }}" class="{{ $linkClasses }}">
                        <span class="course-index-label block">{{ $item->title }}</span>
                    </a>

                    @if ($showCompletionIcon)
                        @if ($isCompleted)
                            <x-coolicon-show class="icon-show text-sol h-6 w-6" />
                        @else
                            <x-coolicon-hide class="icon-hide text-morado1 h-6 w-6" />
                        @endif
                    @else
                        <span class="course-index-placeholder-icon block h-6 w-6"></span>
                    @endif

                    @if ($hasChildren)
                        <button type="button" class="course-index-toggle rounded-xs bg-blanco/10 h-7 w-7 text-sm"
                            data-toggle-children="true" aria-expanded="true"
                            aria-label="Mostrar u ocultar sublecciones">
                            <span class="course-index-chevron font-bold leading-none">-</span>
                        </button>
                    @else
                        <span class="course-index-placeholder-icon block h-7 w-7" aria-hidden="true"></span>
                    @endif
                </div>
            @else
                <div class="{{ $inactiveRowClasses }}">
                    <span class="course-index-label flex-1">{{ $item->title }}</span>
                    <span class="course-index-placeholder-icon block h-6 w-6"></span>
                    @if ($hasChildren)
                        <button type="button" class="course-index-toggle rounded-xs bg-blanco/10 h-7 w-7 text-sm"
                            data-toggle-children="true" aria-expanded="true"
                            aria-label="Mostrar u ocultar sublecciones">
                            <span class="course-index-chevron font-bold leading-none">-</span>
                        </button>
                    @endif
                </div>
            @endif

            @if ($hasChildren)
                <div class="course-index-children-wrapper" data-children-wrapper>
                    <ul class="course-index-children !mb-0 space-y-2 border-l border-gray-200 pl-6">
                        @include('partials.course-index-item', [
                            'items' => $item->children,
                            'level' => $currentLevel + 1,
                        ])
                    </ul>
                </div>
            @endif
        </li>
    </ul>
@endforeach
