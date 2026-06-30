@extends('layouts.guest')

@section('content')
<div class="card bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title justify-center text-2xl mb-4">Reimposta la password</h2>

        @if ($errors->any())
            <div class="alert alert-error mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text">Email</span>
                </label>
                <input type="email" name="email"
                       value="{{ old('email', $request->email) }}"
                       required readonly
                       class="input input-bordered w-full @error('email') input-error @enderror" />
            </div>

            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text">Nuova password</span>
                </label>
                <input type="password" name="password" required autofocus
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
                Salva nuova password
            </button>
        </form>

        <div class="divider my-4">oppure</div>

        <p class="text-center text-sm text-base-content/70 mb-3">
            Hai già impostato la password?
        </p>
        <a href="{{ route('login') }}" class="btn btn-outline w-full">
            Vai al login
        </a>
    </div>
</div>
@endsection
