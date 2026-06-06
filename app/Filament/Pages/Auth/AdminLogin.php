<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;

class AdminLogin extends Login
{
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        $authGuard = Filament::auth();
        $authProvider = $authGuard->getProvider();
        $credentials = $this->getCredentialsFromFormData($data);
        $user = $authProvider->retrieveByCredentials($credentials);

        if (
            $user &&
            $authProvider->validateCredentials($user, $credentials) &&
            $user instanceof FilamentUser &&
            (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()))
        ) {
            session()->forget('url.intended');
            session()->flash('error', 'Non puoi entrare qui. Usa la login utenti.');

            $this->redirectRoute('login');

            return null;
        }

        return parent::authenticate();
    }
}
