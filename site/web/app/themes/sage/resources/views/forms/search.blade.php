@php($search_input_id = $search_input_id ?? 'site-search')

<form role="search" method="get" action="{{ home_url('/') }}" class="font-sans">
  <label for="{{ $search_input_id }}" class="text-gris1 mb-3 block text-sm">
    {{ __('Escribe una palabra o un tema', 'sage') }}
  </label>

  <div class="flex flex-col gap-3 sm:flex-row">
    <input id="{{ $search_input_id }}" type="search" name="s" value="{{ get_search_query() }}"
      placeholder="{{ __('¿Qué te gustaría encontrar?', 'sage') }}"
      class="bg-negro/80 text-blanco placeholder:text-gris2 min-h-12 w-full min-w-0 rounded-sm border border-gris2 px-4 py-3 text-base focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-morado2">
    <button type="submit"
      class="btn btn--morado3 min-h-12 shrink-0 px-7 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-morado2 motion-reduce:transition-none">
      {{ __('Buscar', 'sage') }}
    </button>
  </div>
</form>
