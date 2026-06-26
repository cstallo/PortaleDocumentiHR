<x-filament-panels::page>
    {{ $this->form }}

    <div class="mt-6 flex gap-3">
        {{ $this->salvaBozzaAction }}
        {{ $this->pubblicaAction }}
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
