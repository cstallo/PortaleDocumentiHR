<x-filament-panels::page>
    <form wire:submit="analizza">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" size="lg">
                Analizza PDF
            </x-filament::button>
        </div>
    </form>

    @if ($esito)
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">Esito</x-slot>
                <p class="text-sm">{{ $esito }}</p>
            </x-filament::section>
        </div>
    @endif

    @if ($testoEstratto)
        <div class="mt-4">
            <x-filament::section>
                <x-slot name="heading">Testo grezzo estratto</x-slot>
                <pre class="text-xs whitespace-pre-wrap overflow-auto max-h-[32rem] p-3 rounded bg-gray-50 dark:bg-gray-900">{{ $testoEstratto }}</pre>
            </x-filament::section>
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
