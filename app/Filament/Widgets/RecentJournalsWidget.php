<?php

namespace App\Filament\Widgets;

use App\Models\JournalEntry;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentJournalsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Jurnal Terbaru';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'finance']) ?? false;
    }

    public function table(Table $table): Table
    {
        $query = JournalEntry::query()->posted();

        return $table
            ->query($query->latest('entry_date')->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Tanggal')
                    ->date('d M Y'),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Referensi')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->wrap(),
                Tables\Columns\TextColumn::make('total_debit')
                    ->label('Total Debit')
                    ->getStateUsing(fn ($record) => (float)$record->lines()->sum('debit'))
                    ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                    ->alignEnd(),
                Tables\Columns\IconColumn::make('is_closing')
                    ->label('Penutup')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('gray'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (JournalEntry $record): string => \App\Filament\Resources\JournalEntryResource::getUrl('view', ['record' => $record])),
            ])
            ->recordUrl(
                fn (JournalEntry $record): string => \App\Filament\Resources\JournalEntryResource::getUrl('view', ['record' => $record])
            )
            ->paginated(false);
    }
}
