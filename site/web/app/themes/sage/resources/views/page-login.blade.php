@extends('layouts.app')

@php
    // Redirige si ya está logueado
    if (is_user_logged_in()) {
        wp_redirect(home_url());
        exit();
    }

    $error_message = null;

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['login_nonce']) &&
        wp_verify_nonce($_POST['login_nonce'], 'custom_login')
    ) {
        $creds = [
            'user_login' => sanitize_user($_POST['username']),
            'user_password' => $_POST['password'],
            'remember' => isset($_POST['remember']),
        ];

        $user = wp_signon($creds);

        if (is_wp_error($user)) {
            $error_message = $user->get_error_message();
        } else {
            $redirect_url = $_POST['redirect_to'] ?? home_url('/');
            wp_safe_redirect($redirect_url);
            exit();
        }
    }
@endphp

@section('content')
    <div class="relative -top-10 flex min-h-screen items-center justify-center bg-gray-950 font-sans text-white">
        <div class="w-full max-w-md rounded-sm bg-gray-900 px-6 py-8 shadow-xl">
            <h1 class="mb-6 text-center text-2xl">Iniciar sesión</h1>

            @if ($error_message)
                <div class="mb-4 rounded bg-red-500 p-3 text-white">{{ $error_message }}</div>
            @endif

            <form method="POST" action="{{ esc_url(home_url('/login')) }}" class="space-y-6">
                @csrf
                <input type="hidden" name="login_nonce" value="{{ wp_create_nonce('custom_login') }}">
                <input type="hidden" name="redirect_to" value="{{ esc_url($_GET['redirect_to'] ?? home_url()) }}">

                <div>
                    <label for="username" class="mb-1 block text-sm">Usuario o correo</label>
                    <input type="text" id="username" name="username"
                        class="w-full rounded border border-gray-700 bg-gray-800 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm">Contraseña</label>
                    <input type="password" id="password" name="password"
                        class="w-full rounded border border-gray-700 bg-gray-800 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="remember" class="mr-2">
                        Recuérdame
                    </label>
                    <a href="{{ wp_lostpassword_url() }}" class="text-sm text-blue-400 hover:underline">¿Olvidaste tu
                        contraseña?</a>
                </div>

                <button type="submit"
                    class="w-full rounded bg-blue-600 px-4 py-2 font-semibold text-white transition hover:bg-blue-700">Entrar</button>
            </form>
        </div>
    </div>
@endsection
