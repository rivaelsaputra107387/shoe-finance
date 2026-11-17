<?php

namespace App\Filament\Widgets;

use App\Models\JournalEntry;
use App\Filament\Resources\JournalEntryResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StaffDraftJournalsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('staff') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JournalEntry::query()
                    ->where('status', 'draft')
                    ->where('created_by', auth()->id())
                    ->latest('entry_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Referensi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Nominal')
                    ->money('IDR')
                    ->state(function (JournalEntry $record) {
                        return $record->lines()->sum('debit');
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Lengkapi Jurnal')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (JournalEntry $record): string => JournalEntryResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Tidak Ada Draft')
            ->emptyStateDescription('Hebat! Semua pekerjaan Anda sudah selesai.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}
