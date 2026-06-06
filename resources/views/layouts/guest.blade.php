<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 flex items-center justify-center">
    <div class="w-full max-w-md px-4">
        <!-- Logo -->
        <div class="flex justify-center mb-8">
            <img src="{{ asset('images/logo_inout.png') }}"
                 alt="{{ config('app.name') }}"
                 class="h-16 w-auto">
        </div>

        <!-- Contenuto pagina (login form, ecc.) -->
        {{ $slot ?? '' }}
        @yield('content')
    </div>
</body>
</html>
