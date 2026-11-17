<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DraftJournalResource\Pages;
use App\Models\JournalEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class DraftJournalResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationLabel = 'Draft Jurnal';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $modelLabel = 'Draft Jurnal';

    protected static ?string $pluralModelLabel = 'Draft Jurnal';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
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

                Tables\Columns\TextColumn::make('lines_account')
                    ->label('Akun COA')
                    ->getStateUsing(function (JournalEntry $record) {
                        $html = '<div class="flex flex-col text-sm whitespace-nowrap">';
                        foreach ($record->lines->sortByDesc('debit') as $line) {
                            $indent = $line->credit > 0 ? 'pl-4' : '';
                            $html .= '<div class="py-2 flex flex-col leading-tight ' . $indent . '">';
                            $html .= '<span class="font-mono text-gray-500">' . ($line->account->code ?? '') . '</span>';
                            $html .= '<span class="font-medium">' . ($line->account->name ?? '') . '</span>';
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                        return new \Illuminate\Support\HtmlString($html);
                    }),

                Tables\Columns\TextColumn::make('lines_debit')
                    ->label('Debit')
                    ->alignEnd()
                    ->getStateUsing(function (JournalEntry $record) {
                        $html = '<div class="flex flex-col text-sm tabular-nums whitespace-nowrap text-gray-700 dark:text-gray-300">';
                        foreach ($record->lines->sortByDesc('debit') as $line) {
                            $html .= '<div class="py-2 flex flex-col leading-tight items-end">';
                            if ($line->debit > 0) {
                                $html .= '<span class="text-xs text-gray-500">Rp</span>';
                                $html .= '<span>' . number_format($line->debit, 2, ',', '.') . '</span>';
                            } else {
                                $html .= '<span class="text-xs opacity-0">Rp</span>';
                                $html .= '<span class="text-gray-400">-</span>';
                            }
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                        return new \Illuminate\Support\HtmlString($html);
                    }),

                Tables\Columns\TextColumn::make('lines_credit')
                    ->label('Kredit')
                    ->alignEnd()
                    ->getStateUsing(function (JournalEntry $record) {
                        $html = '<div class="flex flex-col text-sm tabular-nums whitespace-nowrap text-gray-700 dark:text-gray-300">';
                        foreach ($record->lines->sortByDesc('debit') as $line) {
                            $html .= '<div class="py-2 flex flex-col leading-tight items-end">';
                            if ($line->credit > 0) {
                                $html .= '<span class="text-xs text-gray-500">Rp</span>';
                                $html .= '<span>' . number_format($line->credit, 2, ',', '.') . '</span>';
                            } else {
                                $html .= '<span class="text-xs opacity-0">Rp</span>';
                                $html .= '<span class="text-gray-400">-</span>';
                            }
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                        return new \Illuminate\Support\HtmlString($html);
                    }),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'unapproved' => 'info',
                        'posted' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_balanced')
                    ->label('Balance')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->defaultSort('entry_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('fiscal_period_id')
                    ->label('Periode')
                    ->relationship('fiscalPeriod', 'name'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Action Khusus untuk melengkapi Jurnal Mutasi Bank
                    Tables\Actions\Action::make('complete_mutation')
                        ->label('Lengkapi Akun')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->visible(function (JournalEntry $record) {
                            if ($record->status !== 'draft') return false;
                            if (!auth()->user()?->hasAnyRole(['owner', 'finance'])) return false;
                            
                            // Cek apakah jurnal ini punya garis Suspense Account
                            $hasSuspense = $record->lines()->whereHas('account', fn($q) => $q->where('code', '9999'))->exists();
                            $isMutation = \App\Models\BankMutation::where('journal_entry_id', $record->id)->exists();
                            
                            return $hasSuspense && $isMutation;
                        })
                        ->url(fn (JournalEntry $record): string => JournalEntryResource::getUrl('edit', ['record' => $record])),

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (JournalEntry $record) => $record->status === 'draft'),
                ])->button()->label('Aksi')->icon('heroicon-m-chevron-down'),
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
            'index' => Pages\ListDraftJournals::route('/'),
            // Kita pinjam view milik JournalEntryResource saja, tapi kita harus mendaftarkan ViewAction dengan url baru
            // Tapi karena ViewAction tidak ada form edit, kita bisa saja pakai halaman List aja.
        ];
    }

    /**
     * Tampilkan HANYA jurnal yang menggunakan akun sementara (Draft Jurnal Mutasi)
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole('staff')) {
            $query->where('created_by', auth()->id());
        }

        // HANYA tampilkan jurnal yang menggunakan akun sementara 9999
        $query->whereHas('lines.account', function ($q) {
            $q->where('code', '9999');
        });

        return $query;
    }
}
