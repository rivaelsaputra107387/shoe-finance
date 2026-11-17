<?php

namespace App\Filament\Resources\JournalEntryResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'Detail Baris Jurnal';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account.code')
                    ->label('Kode Akun')
                    ->sortable(),

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Nama Akun')
                    ->sortable(),

                Tables\Columns\TextColumn::make('debit')
                    ->label('Debit')
                    ->money('IDR')
                    ->alignEnd()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('credit')
                    ->label('Kredit')
                    ->money('IDR')
                    ->alignEnd()
                    ->placeholder('-'),
            ])
            ->paginated(false);
    }
}
