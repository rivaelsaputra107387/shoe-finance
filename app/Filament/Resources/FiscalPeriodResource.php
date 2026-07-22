<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FiscalPeriodResource\Pages;
use App\Models\FiscalPeriod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FiscalPeriodResource extends Resource
{
    protected static ?string $model = FiscalPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Periode Akuntansi';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $title = 'Periode Akuntansi';
    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('owner') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Periode')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Contoh: Juni 2026'),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Tanggal Selesai')
                    ->required()
                    ->afterOrEqual('start_date'),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ])
                    ->default('open')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('closedBy.name')
                    ->label('Ditutup Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('journal_entries_count')
                    ->counts('journalEntries')
                    ->label('Jml Jurnal')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (FiscalPeriod $record) => $record->status === 'open'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (FiscalPeriod $record) => $record->journalEntries()->count() === 0),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('start_date', 'desc');
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
            'index' => Pages\ListFiscalPeriods::route('/'),
            'create' => Pages\CreateFiscalPeriod::route('/create'),
            'edit' => Pages\EditFiscalPeriod::route('/{record}/edit'),
        ];
    }
}
