<?php

namespace App\Filament\Pages;

use App\Models\AuditTrail;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class AuditTrailPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Audit Trail';
    protected static ?string $navigationGroup = 'Keamanan & Audit';
    protected static ?string $title = 'Log Audit Trail';
    protected static ?int $navigationSort = 100;
    protected static string $view = 'filament.pages.audit-trail';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('owner') ?? false;
    }

    public function mount(): void
    {
        if (!auth()->user()?->hasRole('owner')) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AuditTrail::query()->latest('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('table_name')
                    ->label('Tabel')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('record_id')
                    ->label('Record ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'create' => 'success',
                        'update' => 'info',
                        'delete' => 'danger',
                        'close_period' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('table_name')
                    ->label('Tabel')
                    ->options([
                        'journal_entries' => 'Journal Entries',
                        'accounts' => 'Accounts',
                        'fiscal_periods' => 'Fiscal Periods',
                    ]),
                Tables\Filters\SelectFilter::make('action')
                    ->label('Aksi')
                    ->options([
                        'create' => 'Create',
                        'update' => 'Update',
                        'delete' => 'Delete',
                        'close_period' => 'Close Period',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Detail Data')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Perubahan Data')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (AuditTrail $record) => view('filament.components.audit-detail', ['record' => $record])),
            ])
            ->bulkActions([
                // No bulk actions for audit trail
            ])
            ->defaultSort('created_at', 'desc');
    }
}
