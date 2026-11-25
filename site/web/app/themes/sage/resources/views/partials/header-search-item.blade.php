<li class="my-menu-item header-search-menu my-2 inline-block w-full xl:w-auto xl:px-6">
  <button type="button" class="text-(--menu-link-color) relative top-0.75 flex size-8 aspect-square cursor-pointer items-center justify-center rounded border border-white p-0 transition-colors hover:bg-cde focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-morado2"
    data-header-search-trigger aria-label="{{ __('Buscar', 'sage') }}" aria-expanded="false"
    aria-controls="header-search-panel">
    <x-dynamic-component component="tabler-search" class="size-5 shrink-0" aria-hidden="true" />
  </button>

  <ul id="header-search-panel" data-header-search-panel aria-hidden="true" inert
    class="my-child-menu {{ is_admin_bar_showing() ? 'xl:top-40' : 'xl:top-32' }} left-0 h-0 w-full overflow-hidden xl:fixed xl:hidden xl:h-auto xl:w-screen">
    <li class="border-zinc-800 px-0 py-5 first:border-t xl:border-none xl:px-8 xl:py-8 xl:opacity-0">
      <div class="mx-auto w-full max-w-5xl">
        @include('forms.search', ['search_input_id' => 'header-search'])
      </div>
    </li>
  </ul>
</li>
