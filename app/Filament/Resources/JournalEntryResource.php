<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalEntryResource\Pages;
use App\Models\JournalEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Daftar Jurnal';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $modelLabel = 'Jurnal';

    protected static ?string $pluralModelLabel = 'Daftar Jurnal';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        // This resource is read-only; journal creation is via custom Livewire page
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Referensi')
                    ->searchable()
                    ->placeholder('-')
                    ->copyable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->wrap()
                    ->limit(80),

                Tables\Columns\TextColumn::make('total_debit')
                    ->label('Total Debit')
                    ->getStateUsing(fn (JournalEntry $record) => (float)$record->lines()->sum('debit'))
                    ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_credit')
                    ->label('Total Kredit')
                    ->getStateUsing(fn (JournalEntry $record) => (float)$record->lines()->sum('credit'))
                    ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'posted' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_balanced')
                    ->label('Balance')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\IconColumn::make('is_closing')
                    ->label('Jurnal Penutup')
                    ->boolean(),

                Tables\Columns\TextColumn::make('postedBy.name')
                    ->label('Diposting Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Input')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('entry_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('fiscal_period_id')
                    ->label('Periode')
                    ->relationship('fiscalPeriod', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                    ]),

                Tables\Filters\Filter::make('entry_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('entry_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('entry_date', '<=', $date));
                    }),

                Tables\Filters\TernaryFilter::make('is_closing')
                    ->label('Jenis Jurnal')
                    ->placeholder('Semua Jurnal')
                    ->trueLabel('Jurnal Penutup')
                    ->falseLabel('Jurnal Umum'),
            ])
            ->actions([
                Tables\Actions\Action::make('post')
                    ->label('Posting')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (JournalEntry $record) => $record->status === 'draft' && auth()->user()?->hasAnyRole(['owner', 'finance']))
                    ->action(fn (JournalEntry $record) => $record->post()),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Detail Jurnal')
                    ->schema([
                        Infolists\Components\TextEntry::make('entry_date')
                            ->label('Tanggal')
                            ->date('d M Y'),
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Referensi')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Dibuat Oleh'),
                        Infolists\Components\TextEntry::make('fiscalPeriod.name')
                            ->label('Periode')
                            ->badge(),
                        Infolists\Components\IconEntry::make('is_closing')
                            ->label('Jurnal Penutup')
                            ->boolean(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\JournalEntryResource\RelationManagers\LinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'view' => Pages\ViewJournalEntry::route('/{record}'),
        ];
    }

    /**
     * Staff can only see their own journal entries.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole('staff')) {
            $query->where('created_by', auth()->id());
        }

        return $query;
    }
}
