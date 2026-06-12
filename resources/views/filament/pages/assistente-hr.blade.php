<x-filament-panels::page>
    <div
        x-data="{
            focusInput() {
                this.$nextTick(() => this.$refs.domandaInput?.focus())
            },
        }"
        x-on:elabora-risposta.window="$wire.rispondi()"
        x-on:chat-scroll.window="focusInput()"
        x-init="focusInput()"
        style="display:flex; flex-direction:column; height:70vh;"
    >
        {{-- Cronologia messaggi --}}
        <div
            x-on:chat-scroll.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
            style="flex:1; overflow-y:auto; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb; padding:16px; display:flex; flex-direction:column; gap:12px;"
        >
            @forelse ($messaggi as $messaggio)
                @php $isUser = $messaggio['ruolo'] === 'user'; @endphp
                <div style="display:flex; justify-content:{{ $isUser ? 'flex-end' : 'flex-start' }};">
                    <div style="
                        max-width:75%;
                        padding:8px 14px;
                        border-radius:16px;
                        font-size:0.875rem;
                        line-height:1.4;
                        white-space:pre-line;
                        box-shadow:0 1px 1px rgba(0,0,0,0.05);
                        {{ $isUser
                            ? 'background:#2563eb; color:#ffffff; border-bottom-right-radius:4px;'
                            : 'background:#ffffff; color:#111827; border:1px solid #e5e7eb; border-bottom-left-radius:4px;' }}
                    ">{{ $messaggio['testo'] }}</div>
                </div>
            @empty
                <div style="flex:1; display:flex; align-items:center; justify-content:center; text-align:center;">
                    <p style="font-size:0.875rem; color:#6b7280; max-width:24rem;">
                        Fai una domanda sui cedolini di un dipendente —
                        es. <em>"quanto ha preso netto ad aprile 2026 Mario Rossi?"</em>
                    </p>
                </div>
            @endforelse

            {{-- Indicatore "sta scrivendo" --}}
            @if ($inAttesa)
                <div style="display:flex; justify-content:flex-start;">
                    <div style="padding:8px 14px; border-radius:16px; border-bottom-left-radius:4px; background:#ffffff; border:1px solid #e5e7eb; color:#6b7280; font-size:0.875rem;">
                        sta scrivendo…
                    </div>
                </div>
            @endif
        </div>

        {{-- Barra di input --}}
        <form wire:submit="invia" style="margin-top:12px; display:flex; gap:8px;">
            <input
                x-ref="domandaInput"
                type="text"
                wire:model="domanda"
                placeholder="Scrivi la tua domanda…"
                autocomplete="off"
                @disabled($inAttesa)
                style="flex:1; border:1px solid #d1d5db; border-radius:9999px; padding:8px 16px; font-size:0.875rem; color:#111827; outline:none;"
            />
            <x-filament::button type="submit" :disabled="$inAttesa" icon="heroicon-m-paper-airplane">
                Invia
            </x-filament::button>
        </form>
    </div>

    {{-- Modale PDF cedolino --}}
    <div
        x-data="{ open: false }"
        x-on:apri-pdf.window="open = true"
        x-show="open"
        x-cloak
        x-on:click.self="open = false"
        x-on:keydown.escape.window="open = false"
        style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.6); padding:24px;"
    >
        <div style="background:#fff; border-radius:12px; width:100%; max-width:900px; height:85vh; display:flex; flex-direction:column; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 16px; border-bottom:1px solid #e5e7eb;">
                <span style="font-weight:600; font-size:0.9rem; color:#111827;">Cedolino</span>
                <button type="button" x-on:click="open = false" style="border:none; background:transparent; font-size:1.5rem; line-height:1; cursor:pointer; color:#6b7280;">&times;</button>
            </div>
            <iframe x-bind:src="open ? $wire.pdfUrl : ''" style="flex:1; width:100%; border:0;"></iframe>
        </div>
    </div>
</x-filament-panels::page>
