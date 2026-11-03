@foreach ($items as $item)
  @php
    $currentLevel = isset($level) ? $level : 0;
    $isActive = property_exists($item, 'active') ? (bool) $item->active : true;
    $hasChildren = !empty($item->children);
  @endphp
  <ul class="course-index-list">
    <li class="course-index-item" data-has-children="{{ $hasChildren ? 'true' : 'false' }}">
      @php
        $showCompletionIcon = is_user_logged_in();
        $isCompleted = $showCompletionIcon && in_array($item->id, $completed_lessons ?? []);
      @endphp

      @if ($isActive)
        <div class="course-index-row">
          <a href="{{ $item->permalink }}" class="course-index-link">
            <span class="course-index-label">{{ $item->title }}</span>
          </a>

          @if ($showCompletionIcon)
            <button type="button" data-lesson-completion data-post-id="{{ $item->id }}"
              data-lesson-title="{{ $item->title }}" data-completed="{{ $isCompleted ? 'true' : 'false' }}"
              aria-pressed="{{ $isCompleted ? 'true' : 'false' }}"
              aria-label="{{ $isCompleted ? 'Marcar como no vista' : 'Marcar como vista' }}: {{ $item->title }}"
              title="{{ $isCompleted ? 'Marcar como no vista' : 'Marcar como vista' }}"
              class="course-index-completion {{ $isCompleted ? 'completed' : '' }}">
              <x-coolicon-show aria-hidden="true" class="icon-show" />
              <x-coolicon-hide aria-hidden="true" class="icon-hide" />
            </button>
          @else
            <span class="course-index-placeholder-icon"></span>
          @endif

          @if ($hasChildren)
            <button type="button" class="course-index-toggle"
              data-toggle-children="true" aria-expanded="true" aria-label="Mostrar u ocultar sublecciones">
              <span class="course-index-chevron">-</span>
            </button>
          @else
            <span class="course-index-placeholder-icon course-index-placeholder-icon--toggle" aria-hidden="true"></span>
          @endif
        </div>
      @else
        <div class="course-index-inactive">
          <span class="course-index-label">{{ $item->title }}</span>
          <span class="course-index-placeholder-icon"></span>
          @if ($hasChildren)
            <button type="button" class="course-index-toggle"
              data-toggle-children="true" aria-expanded="true" aria-label="Mostrar u ocultar sublecciones">
              <span class="course-index-chevron">-</span>
            </button>
          @endif
        </div>
      @endif

      @if ($hasChildren)
        <div class="course-index-children-wrapper" data-children-wrapper>
          <ul class="course-index-children">
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
