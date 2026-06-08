<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Notifications\InvitoDipendente;
use App\Notifications\InvitoUtente;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    private ?string $passwordTemporanea = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['role'] ?? null) === 'dipendente') {
            // nessuna password digitata: ne mettiamo una casuale inutilizzabile.
            // il dipendente imposterà la sua dal link di invito.
            $data['password'] = Str::random(40);
            $data['must_change_password'] = false;
        } else {
            // hr / super_admin: password digitata + cambio obbligatorio al primo accesso
            $this->passwordTemporanea = $data['password'] ?? null;
            $data['must_change_password'] = true;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->isDipendente()) {
            // genera token e invia il link per impostare la password
            $token = Password::broker()->createToken($this->record);
            $this->record->notify(new InvitoDipendente($token));
        } elseif ($this->passwordTemporanea) {
            $this->record->notify(new InvitoUtente($this->passwordTemporanea));
        }
    }
}
