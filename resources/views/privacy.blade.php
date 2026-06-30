<!DOCTYPE html>
<html lang="it" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informativa privacy — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .informativa h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: .5rem;
        }

        .informativa h2 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: .5rem;
        }

        .informativa ul {
            list-style: disc;
            padding-left: 1.5rem;
            margin: .5rem 0;
        }

        .informativa p {
            margin: .5rem 0;
        }
    </style>

</head>

<body class="min-h-screen bg-base-200 py-10">
    <div class="mx-auto max-w-3xl bg-white rounded-lg shadow p-8 informativa">
        <img src="{{ asset('images/logo_inout.png') }}" alt="{{ config('app.name') }}" class="h-12 mb-6">

        <h1>Informativa sul trattamento dei dati personali</h1>
        <p><em>Ai sensi degli artt. 13-14 del Regolamento UE 2016/679 (GDPR).
                Versione 1.0 — {{ now()->format('d/m/Y') }}.</em></p>

        <h2>1. Titolare del trattamento</h2>
        <p>Titolare del trattamento è la società datrice di lavoro
            <strong>{{ $azienda?->nome ?? 'la tua azienda' }}</strong>
            @if ($azienda?->indirizzo)
                , con sede in {{ $azienda->indirizzo }}
                @endif@if ($azienda?->email_contatto)
                    , contattabile all'indirizzo {{ $azienda->email_contatto }}
                @endif.
        </p>


        <h2>2. Dati trattati</h2>
        <ul>
            <li><strong>Dati anagrafici e identificativi:</strong> nome, cognome, codice fiscale,
                email, sesso, luogo e data di nascita, sede di lavoro.</li>
            <li><strong>Dati sul rapporto di lavoro:</strong> matricola, date di assunzione,
                cessazione ed eventuale scadenza del contratto.</li>
            <li><strong>Documenti di lavoro:</strong> cedolini e buste paga messi a tua disposizione.</li>
        </ul>

        <h2>3. Finalità e base giuridica</h2>
        <p>I dati sono trattati per la <strong>gestione del rapporto di lavoro</strong> e la
            <strong>distribuzione dei documenti di lavoro</strong> (cedolini). La base giuridica è
            l'<strong>obbligo legale</strong> (art. 6.1.c) e l'<strong>esecuzione del contratto</strong>
            di lavoro (art. 6.1.b). Per questi dati non è richiesto il tuo consenso.
        </p>

        <h2>4. Conservazione</h2>
        <p>I dati sono conservati per il tempo previsto dagli obblighi di legge,
            di norma fino a 10 anni dalla cessazione del rapporto di lavoro.</p>


        <h2>5. Comunicazione a terzi</h2>
        <p>I dati non sono comunicati a terzi, salvo obblighi di legge.@if ($azienda?->responsabile_trattamento)
                Il portale è gestito tramite <strong>{{ $azienda->responsabile_trattamento }}</strong>, nominato
                Responsabile del trattamento ex art. 28 GDPR.
            @endif
        </p>


        <h2>6. I tuoi diritti</h2>
        <p>Puoi esercitare i diritti di accesso, rettifica, cancellazione, limitazione,
            opposizione e portabilità (artt. 15-22 GDPR) scrivendo 
            @if ($azienda?->email_contatto)
                a {{ $azienda->email_contatto }}
            @else
                al Titolare del trattamento
            @endif.
            Hai inoltre diritto di proporre reclamo al Garante per la protezione dei dati personali.</p>

        @if ($azienda?->dpo_email)
        <h2>7. Data Protection Officer (DPO)</h2>
            <p>Il DPO è contattabile all'indirizzo {{ $azienda->dpo_email }}.</p>
        @else
            {{-- <p>Il Titolare non ha nominato un Data Protection Officer.</p> --}}
        @endif



        <p class="mt-8"><a href="{{ route('documenti.index') }}" class="text-primary">← Torna alla tua area</a></p>
    </div>
</body>

</html>
