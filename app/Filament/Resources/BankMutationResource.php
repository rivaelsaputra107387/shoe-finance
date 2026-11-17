<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankMutationResource\Pages;
use App\Models\BankMutation;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Colors\Color;
use Filament\Notifications\Notification;
use App\Services\BankMutationService;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BankMutationResource extends Resource
{
    protected static ?string $model = BankMutation::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Transaksi';
    protected static ?string $pluralModelLabel = 'Transaksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->label('Tanggal'),
                Forms\Components\Select::make('bank_source')
                    ->options([
                        'BCA' => 'BCA',
                        'MANDIRI' => 'MANDIRI',
                    ])
                    ->required()
                    ->label('Sumber Bank')
                    ->disabled(fn (?\App\Models\BankMutation $record) => $record !== null),
                Forms\Components\Select::make('mutation_type')
                    ->options([
                        'IN' => 'Uang Masuk (IN)',
                        'OUT' => 'Uang Keluar (OUT)',
                    ])
                    ->required()
                    ->label('Tipe Mutasi')
                    ->disabled(fn (?\App\Models\BankMutation $record) => $record !== null),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->label('Nominal')
                    ->disabled(fn (?\App\Models\BankMutation $record) => $record !== null),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull()
                    ->label('Keterangan Mutasi'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Tanggal'),
                Tables\Columns\TextColumn::make('bank_source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'BCA' => 'info',
                        'MANDIRI' => 'warning',
                        default => 'gray',
                    })
                    ->label('Bank'),
                Tables\Columns\TextColumn::make('mutation_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'IN' => 'success',
                        'OUT' => 'danger',
                        default => 'gray',
                    })
                    ->label('Tipe Transaksi'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->label('Nominal'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(30)
                    ->searchable()
                    ->label('Keterangan'),

                // COA Preview Columns
                Tables\Columns\TextColumn::make('coa_code')
                    ->label('Kode Akun')
                    ->getStateUsing(function (BankMutation $record) {
                        if (!$record->journalEntry) return '-';
                        $line = $record->journalEntry->lines->first(fn($l) => !in_array($l->account?->code, ['1120', '1121', '9999']));
                        return $line ? $line->account?->code : '-';
                    }),
                Tables\Columns\TextColumn::make('coa_name')
                    ->label('Nama Akun')
                    ->getStateUsing(function (BankMutation $record) {
                        if (!$record->journalEntry) return 'Belum Lengkap';
                        $line = $record->journalEntry->lines->first(fn($l) => !in_array($l->account?->code, ['1120', '1121', '9999']));
                        return $line ? $line->account?->name : 'Belum Lengkap';
                    })
                    ->badge()
                    ->color(fn(string $state) => $state === 'Belum Lengkap' ? 'gray' : 'success'),
                Tables\Columns\TextColumn::make('coa_position')
                    ->label('Posisi D/K')
                    ->getStateUsing(function (BankMutation $record) {
                        if (!$record->journalEntry) return '-';
                        $line = $record->journalEntry->lines->first(fn($l) => !in_array($l->account?->code, ['1120', '1121', '9999']));
                        if (!$line) return '-';
                        return $line->debit > 0 ? 'Debit' : 'Kredit';
                    })
                    ->badge()
                    ->color(fn(string $state) => match($state) {
                        'Debit' => 'info',
                        'Kredit' => 'warning',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'matched' => 'info',
                        'drafted' => 'warning',
                        'unapproved' => 'danger',
                        'posted' => 'success',
                        default => 'gray',
                    })
                    ->label('Status'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'matched' => 'Matched (API)',
                        'drafted' => 'Drafted (Isi Akun)',
                        'unapproved' => 'Unapproved (Tunggu Finance)',
                        'posted' => 'Posted (Selesai)',
                    ])
                    ->label('Status Approval'),
                Tables\Filters\SelectFilter::make('bank_source')
                    ->options([
                        'BCA' => 'BCA',
                        'MANDIRI' => 'MANDIRI',
                    ])
                    ->label('Sumber Bank'),
            ])
            ->actions([
                // 1. MATCH API ACTION
                Action::make('match_api')
                    ->label('Match API')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->visible(fn (BankMutation $record) => $record->status === 'pending')
                    ->action(function (BankMutation $record) {
                        $service = new BankMutationService();
                        $service->matchWithApi($record, auth()->id());
                        
                        Notification::make()
                            ->title('Berhasil Match Invoice!')
                            ->success()
                            ->send();
                    }),
                
                // 2. GENERATE DRAFT JURNAL (SUSPENSE ACCOUNT)
                Action::make('generate_draft')
                    ->label('Generate Draft')
                    ->icon('heroicon-o-document-plus')
                    ->color('warning')
                    ->visible(fn (BankMutation $record) => in_array($record->status, ['pending', 'matched']))
                    ->requiresConfirmation()
                    ->action(function (BankMutation $record) {
                        try {
                            $service = new BankMutationService();
                            $service->draftJournalEntry($record, auth()->id());
                            
                            Notification::make()
                                ->title('Jurnal Draft Berhasil Dibuat dengan Akun Sementara')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Membuat Jurnal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\EditAction::make()
                    ->after(function (\App\Models\BankMutation $record) {
                        if ($record->journal_entry_id) {
                            $journalEntry = \App\Models\JournalEntry::find($record->journal_entry_id);
                            if ($journalEntry && in_array($journalEntry->status, ['draft', 'unapproved'])) {
                                $journalEntry->update([
                                    'entry_date' => $record->date,
                                    'description' => $record->description,
                                ]);
                            }
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankMutations::route('/'),
        ];
    }
}
