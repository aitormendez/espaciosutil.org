<div class="border-gris3 relative border-b py-1 font-sans font-light text-white xl:pr-12">
    <div class="mx-auto flex flex-col items-center justify-center sm:flex-row sm:space-x-6 xl:!justify-end">
        @if (!is_user_logged_in())
            <a href="{{ home_url('/login/') }}" data-barba-prevent class="transition hover:text-gray-300">Acceso</a>
        @else
            <a href="{{ home_url('/cuenta-de-membresia') }}" data-barba-prevent class="transition hover:text-gray-300">Mi
                cuenta</a>
            <form method="POST" action="{{ site_url('wp-login.php?action=logout') }}" class="inline">
                @php
                    $logout_nonce = wp_create_nonce('log-out');
                @endphp
                <input type="hidden" name="_wpnonce" value="{{ $logout_nonce }}">
                <input type="hidden" name="redirect_to" value="{{ home_url('/') }}">
                <button type="submit"
                    class="m-0 cursor-pointer border-none bg-transparent p-0 transition hover:text-gray-300">
                    Salir
                </button>
            </form>
        @endif
    </div>
</div>
