<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="practice-icon" fill="{{ in_array($icon, ['left', 'right']) ? 'currentColor' : 'none' }}" stroke="{{ in_array($icon, ['left', 'right']) ? 'none' : 'currentColor' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  @switch($icon)
    @case('right') <path d="M7 6l-.112 .006a1 1 0 0 0 -.669 1.619l3.501 4.375l-3.5 4.375a1 1 0 0 0 .78 1.625h6a1 1 0 0 0 .78 -.375l4 -5a1 1 0 0 0 0 -1.25l-4 -5a1 1 0 0 0 -.78 -.375h-6z"/> @break
    @case('left') <path d="M17 6h-6a1 1 0 0 0 -.78 .375l-4 5a1 1 0 0 0 0 1.25l4 5a1 1 0 0 0 .78 .375h6l.112 -.006a1 1 0 0 0 .669 -1.619l-3.501 -4.375l3.5 -4.375a1 1 0 0 0 -.78 -1.625z"/> @break
    @case('volume') <path d="M15 8a5 5 0 0 1 0 8M17.7 5a9 9 0 0 1 0 14M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5"/> @break
    @case('volume-off') <path d="M15 8a5 5 0 0 1 1.912 4.934m-1.377 2.602a5 5 0 0 1 -.535 .464M17.7 5a9 9 0 0 1 2.362 11.086m-1.676 2.299a9 9 0 0 1 -.686 .615M9.069 5.054l.431 -.554a.8 .8 0 0 1 1.5 .5v2m0 4v8a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l1.294 -1.664M3 3l18 18"/> @break
    @case('maximize') <path d="M16 4h4v4M14 10l6 -6M8 20h-4v-4M4 20l6 -6M16 20h4v-4M14 14l6 6M8 4h-4v4M4 4l6 6"/> @break
    @case('minimize') <path d="M5 9h4v-4M3 3l6 6M5 15h4v4M3 21l6 -6M19 9h-4v-4M15 9l6 -6M19 15h-4v4M15 15l6 6"/> @break
    @case('index') <path d="M4 6a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2zM9 4v16M14 10l2 2l-2 2"/> @break
  @endswitch
</svg>
