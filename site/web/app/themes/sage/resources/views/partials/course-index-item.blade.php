@foreach ($items as $item)
  <li class="course-index-item">
    <a href="{{ $item->permalink }}" class="text-lg font-semibold text-blue-600 hover:underline">
      {{ $item->title }}
    </a>

    @if (!empty($item->children))
      <ul class="course-index-children mt-2 ml-6 space-y-2 border-l border-gray-200 pl-6">
        @include('partials.course-index-item', ['items' => $item->children])
      </ul>
    @endif
  </li>
@endforeach
