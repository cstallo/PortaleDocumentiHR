{{-- @extends('layouts.guest')

@section('content')
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h1 class="card-title justify-center text-2xl">Accedi</h1>

            @if ($errors->any())
                <div class="alert alert-error text-sm">
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success text-sm">
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-error text-sm">
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <label class="form-control w-full">
                    <span class="label-text">Email</span>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
                        class="input input-bordered w-full"
                    >
                </label>

                <label class="form-control w-full">
                    <span class="label-text">Password</span>
                    <input
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                        class="input input-bordered w-full"
                    >
                </label>

                <label class="label cursor-pointer justify-start gap-3">
                    <input type="checkbox" name="remember" class="checkbox checkbox-primary">
                    <span class="label-text">Ricordami</span>
                </label>

                <div class="card-actions items-center justify-between pt-2">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="link link-hover text-sm">
                            Password dimenticata?
                        </a>
                    @endif

                    <button type="submit" class="btn btn-primary">
                        Entra
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection --}}

@extends('layouts.guest')

@section('content')
<div class="card bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title justify-center text-2xl mb-4">Accedi</h2>

        @if (session('status'))
            <div class="alert alert-info mb-4">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text">Email</span>
                </label>
                <input type="email" name="email" value="{{ old('email') }}"
                       placeholder="nome@azienda.it" autofocus required
                       class="input input-bordered w-full @error('email') input-error @enderror" />
            </div>

            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text">Password</span>
                </label>
                <input type="password" name="password" required
                       placeholder="••••••••"
                       class="input input-bordered w-full @error('password') input-error @enderror" />
                <label class="label">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                           class="label-text-alt link link-hover">
                            Password dimenticata?
                        </a>
                    @endif
                </label>
            </div>

            <div class="form-control mb-6">
                <label class="label cursor-pointer justify-start gap-3">
                    <input type="checkbox" name="remember" class="checkbox checkbox-sm" />
                    <span class="label-text">Ricordami</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary w-full">
                Accedi
            </button>
        </form>
    </div>
</div>
@endsection

