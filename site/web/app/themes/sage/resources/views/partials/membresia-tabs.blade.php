@php
    $account_page_id = pmpro_getOption('account_page_id');
    $profile_page_id = pmpro_getOption('member_profile_edit_page_id');
    $billing_page_id = pmpro_getOption('billing_page_id');
    $invoice_page_id = pmpro_getOption('invoice_page_id');
    $cancel_page_id = pmpro_getOption('cancel_page_id');
    $checkout_url = pmpro_url('checkout') . '?level=1';
@endphp

<nav class="mb-6 flex w-full justify-center font-sans text-2xl">
    <ul class="flex flex-wrap gap-12">
        @if (is_user_logged_in())
            <li>
                <a href="{{ get_permalink($account_page_id) }}"
                    class="{{ (int) get_the_ID() === (int) $account_page_id ? 'text-morado3' : 'text-gris3' }}">
                    Cuenta
                </a>
            </li>
            <li>
                <a href="{{ get_permalink($profile_page_id) }}"
                    class="{{ (int) get_the_ID() === (int) $profile_page_id ? 'text-morado3' : 'text-gris3' }}">
                    Editar perfil
                </a>
            </li>
            <li>
                <a href="{{ get_permalink($invoice_page_id) }}"
                    class="{{ (int) get_the_ID() === (int) $invoice_page_id ? 'text-morado3' : 'text-gris3' }}">
                    Pedidos
                </a>
            </li>
            @if (pmpro_hasMembershipLevel())
                <li>
                    <a href="{{ get_permalink($billing_page_id) }}"
                        class="{{ (int) get_the_ID() === (int) $billing_page_id ? 'text-morado3' : 'text-gris3' }}">
                        Facturación
                    </a>
                </li>
                <li>
                    <a href="{{ get_permalink($cancel_page_id) }}"
                        class="{{ (int) get_the_ID() === (int) $cancel_page_id ? 'text-morado3' : 'text-gris3' }}">
                        Cancelar
                    </a>
                </li>
            @endif
        @endif

        @if (!is_user_logged_in())
            <li>
                <a href="{{ $checkout_url }}" class="text-gray-600">
                    Inscribirme
                </a>
            </li>
        @endif

        @if (is_user_logged_in())
            @unless (pmpro_hasMembershipLevel())
                <li>
                    <a href="{{ $checkout_url }}" class="text-gray-600">
                        Inscribirme
                    </a>
                </li>
            @endunless
        @endif
    </ul>
</nav>
