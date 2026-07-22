<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class CreateJournalEntry extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationLabel = 'Input Jurnal Baru';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $title = 'Input Jurnal Baru';

    protected static ?string $slug = 'create-journal-entry';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.create-journal-entry';
}
