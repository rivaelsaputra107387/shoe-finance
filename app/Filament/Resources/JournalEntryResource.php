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

    protected static ?int $navigationSort = 3;

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
                        'unapproved' => 'Unapproved',
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('submit_approval')
                        ->label('Submit')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->visible(fn (JournalEntry $record) => $record->status === 'draft' && auth()->user()?->hasAnyRole(['staff', 'owner', 'finance']))
                        ->action(function (JournalEntry $record) {
                            $record->update([
                                'status' => 'unapproved',
                                'submitted_by' => auth()->id(),
                                'submitted_at' => now(),
                            ]);
                            \App\Models\BankMutation::where('journal_entry_id', $record->id)->update(['status' => 'unapproved']);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil di-submit untuk Approval')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('approve_post')
                        ->label('Approve & Post')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (JournalEntry $record) => in_array($record->status, ['draft', 'unapproved']) && auth()->user()?->hasAnyRole(['owner', 'finance']))
                        ->action(function (JournalEntry $record) {
                            // Cek jika masih ada Akun Sementara (9999)
                            if ($record->lines()->whereHas('account', fn($q) => $q->where('code', '9999'))->exists()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal Posting')
                                    ->body('Harap Lengkapi Akun terlebih dahulu (Ganti Akun Sementara 9999) sebelum melakukan posting.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            $record->post(); // Uses existing model logic
                            \App\Models\BankMutation::where('journal_entry_id', $record->id)->update(['status' => 'posted', 'posted_by' => auth()->id()]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Jurnal berhasil di-posting')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (JournalEntry $record) => $record->status === 'unapproved' && auth()->user()?->hasAnyRole(['owner', 'finance']))
                        ->action(function (JournalEntry $record) {
                            $record->update([
                                'status' => 'draft',
                                'submitted_by' => null,
                                'submitted_at' => null,
                            ]);
                            \App\Models\BankMutation::where('journal_entry_id', $record->id)->update(['status' => 'drafted']);
                        }),

                    Tables\Actions\EditAction::make()
                        ->visible(fn (JournalEntry $record) => in_array($record->status, ['draft', 'unapproved'])),
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
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
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

        // Jangan tampilkan jurnal yang masih menggunakan akun sementara (Draft Jurnal Mutasi)
        $query->whereDoesntHave('lines.account', function ($q) {
            $q->where('code', '9999');
        });

        return $query;
    }
}
