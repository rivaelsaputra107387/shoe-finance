<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Filament\Notifications\Notification;

class JournalEntryForm extends Component
{
    // Header fields
    public string $entry_date = '';
    public string $description = '';
    public string $reference = '';

    // Dynamic rows: each row = ['account_id' => '', 'debit' => 0, 'credit' => 0]
    public array $lines = [];

    // Computed display
    public float $totalDebit = 0;
    public float $totalCredit = 0;
    public float $difference = 0;

    // Available data
    public $accounts = [];
    public $activePeriod = null;

    public function mount(): void
    {
        $this->accounts = Account::active()
            ->whereNotNull('parent_id') // Only leaf accounts (not group headers)
            ->orderBy('code')
            ->get()
            ->map(fn ($a) => ['id' => $a->id, 'label' => "{$a->code} - {$a->name}"])
            ->toArray();

        $this->activePeriod = FiscalPeriod::active();

        if ($this->activePeriod) {
            $this->entry_date = now()->format('Y-m-d');
            // Clamp to period range
            if (now()->lt($this->activePeriod->start_date)) {
                $this->entry_date = $this->activePeriod->start_date->format('Y-m-d');
            } elseif (now()->gt($this->activePeriod->end_date)) {
                $this->entry_date = $this->activePeriod->end_date->format('Y-m-d');
            }
        }

        // Start with 2 empty rows
        $this->addLine();
        $this->addLine();
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'account_id' => '',
            'description' => '',
            'debit' => '',
            'credit' => '',
        ];
    }

    public function removeLine(int $index): void
    {
        if (count($this->lines) <= 2) {
            Notification::make()
                ->title('Minimal 2 baris diperlukan')
                ->warning()
                ->send();
            return;
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        $this->calculateTotals();
    }

    public function updated($property): void
    {
        // Recalculate totals whenever any line value changes
        if (str_starts_with($property, 'lines.')) {
            // Ensure mutual exclusivity: if debit is filled, clear credit and vice versa
            $parts = explode('.', $property);
            if (count($parts) === 3) {
                $index = $parts[1];
                $field = $parts[2];

                if ($field === 'debit' && floatval($this->lines[$index]['debit'] ?? 0) > 0) {
                    $this->lines[$index]['credit'] = '';
                } elseif ($field === 'credit' && floatval($this->lines[$index]['credit'] ?? 0) > 0) {
                    $this->lines[$index]['debit'] = '';
                }
            }

            $this->calculateTotals();
        }
    }

    public function calculateTotals(): void
    {
        $this->totalDebit = 0;
        $this->totalCredit = 0;

        foreach ($this->lines as $line) {
            $this->totalDebit += floatval($line['debit'] ?? 0);
            $this->totalCredit += floatval($line['credit'] ?? 0);
        }

        $this->difference = round($this->totalDebit - $this->totalCredit, 2);
    }

    public function getIsBalancedProperty(): bool
    {
        return abs($this->difference) < 0.01 && $this->totalDebit > 0;
    }

    public function getCanSaveProperty(): bool
    {
        if (!$this->is_balanced) {
            return false;
        }

        if (!$this->activePeriod || !$this->activePeriod->is_open) {
            return false;
        }

        if (empty($this->entry_date) || empty($this->description)) {
            return false;
        }

        // Check date is within active period
        if (!$this->activePeriod->containsDate($this->entry_date)) {
            return false;
        }

        // Check all lines have account selected
        $validLines = 0;
        foreach ($this->lines as $line) {
            if (empty($line['account_id'])) {
                continue;
            }
            $debit = floatval($line['debit'] ?? 0);
            $credit = floatval($line['credit'] ?? 0);
            if ($debit > 0 || $credit > 0) {
                $validLines++;
            }
        }

        return $validLines >= 2;
    }

    public function save()
    {
        // Server-side validation
        $this->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|min:3',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string|max:255',
        ]);

        if (!$this->can_save) {
            Notification::make()
                ->title('Tidak bisa menyimpan')
                ->body('Pastikan total debit = total kredit, tanggal dalam periode aktif, dan minimal 2 baris.')
                ->danger()
                ->send();
            return;
        }

        try {
            DB::transaction(function () {
                $journalEntry = JournalEntry::create([
                    'entry_date' => $this->entry_date,
                    'reference' => $this->reference ?: null,
                    'description' => $this->description,
                    'fiscal_period_id' => $this->activePeriod->id,
                    'created_by' => auth()->id(),
                    'is_closing' => false,
                    'status' => auth()->user()->hasRole('staff') ? 'draft' : 'posted',
                    'posted_by' => auth()->user()->hasRole('staff') ? null : auth()->id(),
                    'posted_at' => auth()->user()->hasRole('staff') ? null : now(),
                ]);

                foreach ($this->lines as $line) {
                    $debit = floatval($line['debit'] ?? 0);
                    $credit = floatval($line['credit'] ?? 0);

                    if (empty($line['account_id']) || ($debit == 0 && $credit == 0)) {
                        continue;
                    }

                    JournalEntryLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $line['account_id'],
                        'description' => $line['description'] ?? null,
                        'debit' => $debit,
                        'credit' => $credit,
                    ]);
                }

                // Final validation: check balance at database level
                $totals = $journalEntry->lines()
                    ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                    ->first();

                if (round($totals->total_debit, 2) !== round($totals->total_credit, 2)) {
                    throw new \Exception('Debit dan Kredit tidak seimbang setelah penyimpanan.');
                }
            });

            Notification::make()
                ->title('Jurnal berhasil disimpan!')
                ->success()
                ->send();

            // Reset form
            $this->reset(['description', 'reference']);
            $this->lines = [];
            $this->addLine();
            $this->addLine();
            $this->calculateTotals();

            // Redirect to journal list
            return redirect()->route('filament.admin.resources.journal-entries.index');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menyimpan jurnal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.journal-entry-form');
    }
}
