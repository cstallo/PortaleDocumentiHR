@extends('layouts.guest')

@section('content')
<div class="card bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title justify-center text-2xl mb-1">Imposta la tua password</h2>
        <p class="text-sm text-center text-base-content/70 mb-4">
            Primo accesso: scegli una nuova password personale per continuare.
        </p>

        @if ($errors->any())
            <div class="alert alert-error mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.change.update') }}">
            @csrf

            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text">Password temporanea (ricevuta via email)</span>
                </label>
                <input type="password" name="current_password" required autofocus
                       autocomplete="off"
                       placeholder="••••••••"
                       class="input input-bordered w-full @error('current_password') input-error @enderror" />
            </div>

            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text">Nuova password</span>
                </label>
                <input type="password" name="password" required
                       autocomplete="new-password"
                       placeholder="Almeno 8 caratteri, lettere e numeri"
                       class="input input-bordered w-full @error('password') input-error @enderror" />
            </div>

            <div class="form-control mb-6">
                <label class="label">
                    <span class="label-text">Conferma nuova password</span>
                </label>
                <input type="password" name="password_confirmation" required
                       autocomplete="new-password"
                       placeholder="Ripeti la nuova password"
                       class="input input-bordered w-full" />
            </div>

            <button type="submit" class="btn btn-primary w-full">
                Salva e accedi
            </button>
        </form>
    </div>
</div>
@endsection
