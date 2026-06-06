@extends('layouts.guest')

@section('content')
<div class="w-full max-w-3xl mx-auto py-8 px-4">

    {{-- Header con logout --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold">I miei documenti</h1>
            <p class="text-sm text-base-content/60 mt-1">
                {{ auth()->user()->name }} — {{ auth()->user()->azienda->nome ?? '' }}
            </p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">
                Esci
            </button>
        </form>
    </div>

    {{-- Messaggio stato --}}
    @if (session('status'))
        <div class="alert alert-info mb-6">{{ session('status') }}</div>
    @endif

    {{-- Sezione cedolini --}}
    <div class="card bg-base-100 shadow mb-6">
        <div class="card-body">
            <h2 class="card-title text-lg mb-4">
                Cedolini e buste paga
            </h2>

            @if ($documenti->isEmpty())
                <p class="text-base-content/60">Nessun documento disponibile.</p>
            @else
                <div class="space-y-3">
                    @foreach ($documenti as $doc)
                    <div class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
                        <div>
                            <p class="font-medium text-sm">{{ $doc->nome_file }}</p>
                            <p class="text-xs text-base-content/60">
                                {{ $doc->cartellaMese->label ?? '' }}
                                @if($doc->scaricato_il)
                                    &mdash; Scaricato il {{ $doc->scaricato_il->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('documenti.download', $doc) }}"
                           class="btn btn-primary btn-sm">
                            Scarica
                        </a>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $documenti->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
