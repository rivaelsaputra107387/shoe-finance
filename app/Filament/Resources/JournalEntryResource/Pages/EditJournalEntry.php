<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Filament\Resources\JournalEntryResource;
use App\Models\JournalEntry;
use Filament\Resources\Pages\Page;

class EditJournalEntry extends Page
{
    protected static string $resource = JournalEntryResource::class;

    protected static string $view = 'filament.pages.edit-journal-entry';

    public JournalEntry $record;

    public function mount(JournalEntry $record): void
    {
        $this->record = $record;
    }
}
