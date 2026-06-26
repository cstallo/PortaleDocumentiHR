<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 flex flex-col">

    {{-- Topbar --}}
    <header class="navbar bg-base-100 shadow-sm px-4 sm:px-6 sticky top-0 z-10">
        <div class="flex-1 flex items-center gap-3 min-w-0">
            <img src="{{ asset('images/logo_inout.png') }}"
                 alt="{{ config('app.name') }}"
                 class="h-9 w-auto shrink-0">
            <div class="leading-tight min-w-0">
                <p class="font-semibold truncate">I miei documenti</p>
                <p class="hidden sm:block text-xs text-base-content/60 truncate">
                    {{ auth()->user()->name }} — {{ auth()->user()->azienda->nome ?? '' }}
                </p>
            </div>
        </div>
        <div class="flex-none">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm">Esci</button>
            </form>
        </div>
    </header>

    {{-- Contenuto --}}
    <main class="flex-1 w-full max-w-6xl mx-auto px-4 sm:px-6 py-6">
        @if (session('status'))
            <div class="alert alert-info mb-6">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>

</body>
</html>
