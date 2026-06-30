<?php

namespace App\Filament\Resources\EmailLogs\Pages;

use App\Filament\Resources\EmailLogs\EmailLogResource;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListEmailLogs extends ListRecords
{
    protected static string $resource = EmailLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('svuota')
                ->label('Svuota fino a data')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Svuota registro email')
                ->modalDescription('Cancella definitivamente i log creati fino alla data scelta (inclusa). Operazione irreversibile.')
                ->schema([
                    DatePicker::make('fino_a')
                        ->label('Cancella i log fino al')
                        ->required()
                        ->default(now()->subMonth()),
                ])
                ->action(function (array $data): void {
                    $eliminati = EmailLogResource::getEloquentQuery()
                        ->whereDate('created_at', '<=', $data['fino_a'])
                        ->delete();

                    Notification::make()
                        ->title("Eliminati {$eliminati} log")
                        ->success()
                        ->send();
                }),
        ];
    }
}
