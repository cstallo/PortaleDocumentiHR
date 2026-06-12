<?php

namespace App\Filament\Resources\BotQueryLogs\Tables;

use App\Models\BotQueryLog;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class BotQueryLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Da')
                    ->placeholder('—'),
                TextColumn::make('dipendente.name')
                    ->label('Su')
                    ->placeholder('—'),
                TextColumn::make('domanda')
                    ->label('Domanda')
                    ->wrap()
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('esito')
                    ->label('Esito')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'risolta' => 'success',
                        'non_risolta' => 'warning',
                        'errore' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('motivo')
                    ->label('Motivo')
                    ->placeholder('—')
                    ->wrap(),
                IconColumn::make('gestita')
                    ->label('Gestita')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('esito')
                    ->options([
                        'risolta' => 'Risolta',
                        'non_risolta' => 'Non risolta',
                        'errore' => 'Errore',
                    ]),
                Filter::make('da_gestire')
                    ->label('Solo da gestire')
                    ->query(fn ($query) => $query->where('esito', 'non_risolta')->where('gestita', false))
                    ->default(),
            ])
            ->recordActions([
                Action::make('gestita')
                    ->label('Segna gestita')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (BotQueryLog $record): bool => ! $record->gestita)
                    ->action(fn (BotQueryLog $record) => $record->update(['gestita' => true])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('segnaGestite')
                        ->label('Segna come gestite')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['gestita' => true]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
