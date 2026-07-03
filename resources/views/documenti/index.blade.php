@extends('layouts.dipendente')

@section('content')

    {{-- Tab CSS-only: i radio nascosti pilotano via `peer-checked` sia lo stato attivo
         delle label sia la visibilità dei pannelli. Le label sono HTML → possono
         contenere un badge. Nessun JavaScript. Radio, label e pannelli devono restare
         tutti figli diretti dello stesso contenitore (il selettore peer agisce sui fratelli). --}}
    <div class="flex flex-wrap items-center gap-1 border-b border-base-300">

        {{-- radio di stato (nascosti) --}}
        <input type="radio" name="doc_tabs" id="tab_cedolini" class="hidden peer/cedolini" checked />
        <input type="radio" name="doc_tabs" id="tab_documenti" class="hidden peer/documenti" />
        <input type="radio" name="doc_tabs" id="tab_bacheca" class="hidden peer/bacheca" />

        {{-- etichette --}}
        <label for="tab_cedolini"
               class="cursor-pointer flex items-center gap-2 px-4 py-2 -mb-px text-sm font-medium border-b-2 border-transparent text-base-content/60 hover:text-base-content peer-checked/cedolini:border-primary peer-checked/cedolini:text-base-content">
            Cedolini
            @if ($cedoliniNuovi > 0)
                <span class="badge badge-error badge-sm text-white">{{ $cedoliniNuovi }}</span>
            @endif
        </label>

        <label for="tab_documenti"
               class="cursor-pointer flex items-center gap-2 px-4 py-2 -mb-px text-sm font-medium border-b-2 border-transparent text-base-content/60 hover:text-base-content peer-checked/documenti:border-primary peer-checked/documenti:text-base-content">
            Documenti
            @if ($documentiNuovi > 0)
                <span class="badge badge-error badge-sm text-white">{{ $documentiNuovi }}</span>
            @endif
        </label>

        <label for="tab_bacheca"
               class="cursor-pointer flex items-center gap-2 px-4 py-2 -mb-px text-sm font-medium border-b-2 border-transparent text-base-content/60 hover:text-base-content peer-checked/bacheca:border-primary peer-checked/bacheca:text-base-content">
            Bacheca
            @if ($nonLettiCount > 0)
                <span class="badge badge-error badge-sm text-white">{{ $nonLettiCount }}</span>
            @endif
        </label>

        {{-- Pannello Cedolini --}}
        <div class="hidden peer-checked/cedolini:block basis-full order-last mt-6">
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h2 class="card-title text-lg mb-2">Cedolini e buste paga</h2>

                    @if ($cedolini->isEmpty())
                        <p class="text-base-content/60">Nessun cedolino disponibile.</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($cedolini as $doc)
                             <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3 bg-base-200 rounded-lg">
                                    <div class="min-w-0">
                                        <p class="font-medium text-sm truncate">{{ $doc->nome_file }}</p>
                                        <p class="text-xs text-base-content/60">
                                            {{ $doc->cartellaMese->label ?? '' }}
                                            @if ($doc->scaricato_il)
                                                &mdash; Scaricato il {{ $doc->scaricato_il->format('d/m/Y') }}
                                            @else
                                                &mdash; <span class="text-error font-medium">Nuovo</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex gap-2 self-start shrink-0">
                                        <a href="{{ route('documenti.inline', $doc) }}"
                                           target="_blank" rel="noopener"
                                           class="btn btn-outline btn-sm">
                                            Apri
                                        </a>
                                        <a href="{{ route('documenti.download', $doc) }}"
                                           class="btn btn-primary btn-sm">
                                            Scarica
                                        </a>
                                    </div>

                                    </a>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">{{ $cedolini->links() }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Pannello Documenti personali (CU, contratti, altro) --}}
        <div class="hidden peer-checked/documenti:block basis-full order-last mt-6">
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h2 class="card-title text-lg mb-2">Documenti personali</h2>

                    @if ($documenti->isEmpty())
                        <p class="text-base-content/60">Nessun documento disponibile.</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($documenti as $doc)
                                <div class="flex items-center justify-between gap-3 p-3 bg-base-200 rounded-lg">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="badge badge-outline badge-sm shrink-0">{{ $doc->tipo->getLabel() }}</span>
                                            <p class="font-medium text-sm truncate">{{ $doc->nome_file }}</p>
                                            @unless ($doc->scaricato_il)
                                                <span class="badge badge-error badge-sm text-white shrink-0">Nuovo</span>
                                            @endunless
                                        </div>
                                        <p class="text-xs text-base-content/60 mt-1">
                                            @if ($doc->data_documento)
                                                {{ $doc->data_documento->format('d/m/Y') }}
                                            @endif
                                            @if ($doc->descrizione)
                                                &mdash; {{ $doc->descrizione }}
                                            @endif
                                        </p>
                                    </div>
                                    <a href="{{ route('documenti.download', $doc) }}" class="btn btn-primary btn-sm shrink-0">
                                        Scarica
                                    </a>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">{{ $documenti->links() }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Pannello Bacheca --}}
        <div class="hidden peer-checked/bacheca:block basis-full order-last mt-6">
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h2 class="card-title text-lg mb-2">Bacheca</h2>

                    @if ($messaggiBacheca->isEmpty())
                        <p class="text-base-content/60">Nessun messaggio in bacheca.</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($messaggiBacheca as $m)
                                <div class="p-4 bg-base-200 rounded-lg @if ($m->pinned) border-l-4 border-primary @endif">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <h3 class="font-semibold">{{ $m->titolo }}</h3>
                                        @if ($m->pinned)
                                            <span class="badge badge-primary badge-sm shrink-0">📌 In evidenza</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-base-content/60 mb-3">
                                        {{ $m->autore->name ?? 'HR' }}
                                        &mdash; {{ $m->pubblicato_il->format('d/m/Y H:i') }}
                                    </p>
                                    <div class="prose prose-sm max-w-none">
                                        {!! $m->corpo !!}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

@endsection
