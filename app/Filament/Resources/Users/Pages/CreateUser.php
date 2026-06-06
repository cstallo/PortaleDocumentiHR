<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Notifications\InvitoUtente;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    private ?string $passwordTemporanea = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // cattura la password in chiaro PRIMA che venga hashata dal save
        $this->passwordTemporanea = $data['password'] ?? null;

        // chi viene creato dovrà cambiarla al primo accesso
        $data['must_change_password'] = true;

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->passwordTemporanea) {
            $this->record->notify(new InvitoUtente($this->passwordTemporanea));
        }
    }
}
