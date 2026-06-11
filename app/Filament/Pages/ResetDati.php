<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class ResetDati extends Page
{
    protected string $view = 'filament.pages.reset-dati';   // ← già generato, lascialo

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-trash';
    }

    public static function getNavigationLabel(): string
    {
        return 'Reset dati (test)';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Strumenti';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset')
                ->label('Elimina tutti i dati')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Reset completo dei dati')
                ->modalDescription('Cancella documenti, dati estratti e file caricati. Aziende e utenti restano. Operazione IRREVERSIBILE.')
                ->form([
                    TextInput::make('conferma')
                        ->label('Scrivi ELIMINA per confermare')
                        ->required()
                        ->rule('in:ELIMINA'),
                ])
                ->action(function (): void {
                    Artisan::call('cedolini:reset', ['--force' => true]);

                    Notification::make()
                        ->title('Reset completato')
                        ->body('Documenti, dati e file azzerati. Aziende e utenti intatti.')
                        ->success()
                        ->send();
                }),
        ];
    }
}

