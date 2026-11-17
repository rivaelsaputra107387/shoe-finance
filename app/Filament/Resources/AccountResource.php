<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Chart of Accounts';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Akun';

    protected static ?string $pluralModelLabel = 'Chart of Accounts';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Akun')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10)
                            ->placeholder('Contoh: 1110')
                            ->helperText('Kode 4 digit: 1xxx=Aset, 2xxx=Kewajiban, 3xxx=Ekuitas, 4xxx=Pendapatan, 5-8xxx=Beban'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Akun')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Kas'),

                        Forms\Components\Select::make('type')
                            ->label('Tipe Akun')
                            ->options([
                                'Aset' => 'Aset',
                                'Kewajiban' => 'Kewajiban',
                                'Ekuitas' => 'Ekuitas',
                                'Pendapatan' => 'Pendapatan',
                                'Beban' => 'Beban',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                // Auto-set normal_balance and report_category based on type
                                match ($state) {
                                    'Aset' => $set('normal_balance', 'Debet') && $set('report_category', 'Neraca'),
                                    'Kewajiban' => $set('normal_balance', 'Kredit') && $set('report_category', 'Neraca'),
                                    'Ekuitas' => $set('normal_balance', 'Kredit') && $set('report_category', 'Neraca'),
                                    'Pendapatan' => $set('normal_balance', 'Kredit') && $set('report_category', 'Laba Rugi'),
                                    'Beban' => $set('normal_balance', 'Debet') && $set('report_category', 'Laba Rugi'),
                                    default => null,
                                };
                            }),

                        Forms\Components\Select::make('normal_balance')
                            ->label('Saldo Normal')
                            ->options([
                                'Debet' => 'Debet',
                                'Kredit' => 'Kredit',
                            ])
                            ->required(),

                        Forms\Components\Select::make('report_category')
                            ->label('Kategori Laporan')
                            ->options([
                                'Neraca' => 'Neraca',
                                'Laba Rugi' => 'Laba Rugi',
                            ])
                            ->required(),

                        Forms\Components\Select::make('cash_flow_category')
                            ->label('Kategori Arus Kas')
                            ->options([
                                'Operasi' => 'Operasi',
                                'Investasi' => 'Investasi',
                                'Pendanaan' => 'Pendanaan',
                            ])
                            ->nullable()
                            ->helperText('Untuk klasifikasi Laporan Arus Kas'),

                        Forms\Components\Select::make('parent_id')
                            ->label('Akun Induk')
                            ->relationship('parent', 'name', fn (Builder $query) => $query->whereNull('parent_id'))
                            ->getOptionLabelFromRecordUsing(fn (Account $record) => "{$record->code} - {$record->name}")
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Kosongkan jika akun ini adalah kelompok utama'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Akun')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aset' => 'info',
                        'Kewajiban' => 'warning',
                        'Ekuitas' => 'success',
                        'Pendapatan' => 'primary',
                        'Beban' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('normal_balance')
                    ->label('Saldo Normal')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Debet' => 'info',
                        'Kredit' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('report_category')
                    ->label('Laporan')
                    ->badge(),

                Tables\Columns\TextColumn::make('cash_flow_category')
                    ->label('Arus Kas')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('parent.full_name')
                    ->label('Induk')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->defaultSort('code')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Akun')
                    ->options([
                        'Aset' => 'Aset',
                        'Kewajiban' => 'Kewajiban',
                        'Ekuitas' => 'Ekuitas',
                        'Pendapatan' => 'Pendapatan',
                        'Beban' => 'Beban',
                    ]),

                Tables\Filters\SelectFilter::make('report_category')
                    ->label('Kategori Laporan')
                    ->options([
                        'Neraca' => 'Neraca',
                        'Laba Rugi' => 'Laba Rugi',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
