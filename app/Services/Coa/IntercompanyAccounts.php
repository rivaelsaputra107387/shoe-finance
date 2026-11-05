<?php

namespace App\Services\Coa;

use App\Models\Central\Unit;
use Illuminate\Support\Collection;

/**
 * Akun antar unit (Lampiran B PRD). Setiap unit lawan punya kode tetap yang sama di semua unit,
 * jadi pasangan akun bisa dicocokkan otomatis.
 */
class IntercompanyAccounts
{
    /**
     * Akun yang harus dimiliki sebuah unit: satu set untuk setiap unit lain.
     *
     * @return Collection<int, array>
     */
    public function forUnit(Unit $unit): Collection
    {
        return Unit::ordered()->where('id', '!=', $unit->id)->get()
            ->flatMap(fn (Unit $counterparty) => $this->accountsFor($counterparty))
            ->sortBy('code')
            ->values();
    }

    private function accountsFor(Unit $counterparty): array
    {
        $slot = $counterparty->intercompany_slot;

        $base = [
            'cash_flow_category' => null,
            'is_locked' => true,
            'is_intercompany' => true,
            'counterparty_unit_code' => $counterparty->code,
        ];

        return [
            $base + ['code' => (string) (1131 + $slot), 'name' => "Piutang Antar Unit – {$counterparty->name}", 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'parent_code' => '1100'],
            $base + ['code' => (string) (2169 + $slot), 'name' => "Utang Antar Unit – {$counterparty->name}", 'type' => 'Kewajiban', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'parent_code' => '2100'],
            $base + ['code' => (string) (4200 + $slot * 10), 'name' => "Pendapatan Antar Unit – {$counterparty->name}", 'type' => 'Pendapatan', 'normal_balance' => 'Kredit', 'report_category' => 'Laba Rugi', 'parent_code' => '4200'],
            $base + ['code' => (string) (6800 + $slot * 10), 'name' => "Beban Antar Unit – {$counterparty->name}", 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'parent_code' => '6800'],
        ];
    }
}
