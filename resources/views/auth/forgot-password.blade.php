@extends('layouts.guest')

@section('content')
<div class="card bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title justify-center text-2xl mb-1">Password dimenticata</h2>
        <p class="text-sm text-center text-base-content/70 mb-4">
            Inserisci la tua email: ti invieremo un link per reimpostare la password.
        </p>

        @if (session('status'))
            <div class="alert alert-success mb-4">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text">Email</span>
                </label>
                <input type="email" name="email" value="{{ old('email') }}"
                       placeholder="nome@azienda.it" autofocus required
                       class="input input-bordered w-full @error('email') input-error @enderror" />
            </div>

            <button type="submit" class="btn btn-primary w-full">
                Invia link di reset
            </button>

            <div class="text-center mt-4">
                <a href="{{ route('login') }}" class="link link-hover text-sm">
                    Torna al login
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
