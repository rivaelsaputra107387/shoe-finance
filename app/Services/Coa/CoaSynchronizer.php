<?php

namespace App\Services\Coa;

use App\Models\Account;
use App\Models\Central\CoaMaster;
use App\Models\Central\Unit;
use App\Support\UnitContext;
use Illuminate\Support\Collection;

/**
 * Menyamakan akun terkunci sebuah unit dengan master. Sub-akun bebas milik unit tidak disentuh.
 */
class CoaSynchronizer
{
    private const SYNCED_FIELDS = ['name', 'type', 'normal_balance', 'report_category', 'cash_flow_category', 'is_locked', 'is_intercompany', 'counterparty_unit_code'];

    public function __construct(
        private UnitContext $context,
        private IntercompanyAccounts $intercompany,
    ) {}

    /**
     * @return array{changes: string[], warnings: string[]}
     */
    public function sync(Unit $unit, bool $dryRun = false): array
    {
        return $this->context->run($unit, function () use ($unit, $dryRun) {
            $definitions = $this->lockedDefinitions($unit);
            $changes = [];
            $warnings = [];

            foreach ($definitions as $definition) {
                $conflict = $this->conflictWith($definition);

                if ($conflict) {
                    $warnings[] = $conflict;

                    continue;
                }

                $change = $this->syncAccount($definition, $dryRun);

                if ($change) {
                    $changes[] = $change;
                }
            }

            return ['changes' => $changes, 'warnings' => array_merge($warnings, $this->warnings($definitions))];
        });
    }

    /**
     * Salin template sub-akun ke unit baru. Akun yang sudah ada dibiarkan.
     *
     * @return int Jumlah akun yang dibuat
     */
    public function copyTemplate(Unit $unit): int
    {
        return $this->context->run($unit, function () {
            $created = 0;

            foreach (CoaMaster::where('is_template', true)->orderBy('code')->get() as $row) {
                if (Account::withTrashed()->where('code', $row->code)->exists()) {
                    continue;
                }

                Account::create([
                    'code' => $row->code,
                    'name' => $row->name,
                    'type' => $row->type,
                    'normal_balance' => $row->normal_balance,
                    'report_category' => $row->report_category,
                    'cash_flow_category' => $row->cash_flow_category,
                    'parent_id' => Account::where('code', $row->parent_code)->value('id'),
                    'is_active' => true,
                ]);
                $created++;
            }

            return $created;
        });
    }

    private function lockedDefinitions(Unit $unit): Collection
    {
        $master = CoaMaster::where('is_locked', true)->get()->map(fn (CoaMaster $row) => [
            'code' => $row->code,
            'name' => $row->name,
            'type' => $row->type,
            'normal_balance' => $row->normal_balance,
            'report_category' => $row->report_category,
            'cash_flow_category' => $row->cash_flow_category,
            'parent_code' => $row->parent_code,
            'is_locked' => true,
            'is_intercompany' => false,
            'counterparty_unit_code' => null,
        ]);

        // Akun induk lebih dulu, supaya parent sudah ada saat sub-akun dibuat.
        return $master->concat($this->intercompany->forUnit($unit))
            ->sortBy(fn (array $row) => [$row['parent_code'] === null ? 0 : 1, $row['code']])
            ->values();
    }

    /**
     * Kode yang sama bisa saja dipakai akun lain yang sudah berisi transaksi. Akun seperti itu
     * tidak boleh ditimpa diam-diam, karena jurnalnya akan ikut pindah makna.
     */
    private function conflictWith(array $definition): ?string
    {
        $account = Account::withTrashed()->where('code', $definition['code'])->first();

        if (! $account || ! $account->journalEntryLines()->exists()) {
            return null;
        }

        if ($definition['parent_code'] === null) {
            return "DILEWATI {$definition['code']}: akun \"{$account->name}\" berisi jurnal, tidak bisa dijadikan akun induk \"{$definition['name']}\". Pindahkan dulu ke kode lain.";
        }

        if ($account->type !== $definition['type']) {
            return "DILEWATI {$definition['code']}: akun \"{$account->name}\" berisi jurnal dengan tipe {$account->type}, berbeda dari master {$definition['type']}.";
        }

        return null;
    }

    private function syncAccount(array $definition, bool $dryRun): ?string
    {
        $account = Account::withTrashed()->where('code', $definition['code'])->first();
        $parentId = $definition['parent_code'] ? Account::where('code', $definition['parent_code'])->value('id') : null;

        if (! $account) {
            if (! $dryRun) {
                Account::create(array_merge(
                    collect($definition)->only(array_merge(['code'], self::SYNCED_FIELDS))->all(),
                    ['parent_id' => $parentId, 'is_active' => true],
                ));
            }

            return "Buat {$definition['code']} {$definition['name']}";
        }

        $different = collect(self::SYNCED_FIELDS)
            ->filter(fn (string $field) => $account->{$field} != $definition[$field])
            ->all();

        // Saat dry-run, induk yang baru akan dibuat belum punya ID.
        $parentChanged = ($definition['parent_code'] === null && $account->parent_id !== null)
            || ($parentId !== null && $account->parent_id !== $parentId);
        $restored = $account->trashed();

        if ($different === [] && ! $parentChanged && ! $restored) {
            return null;
        }

        if (! $dryRun) {
            if ($restored) {
                $account->restore();
            }

            $account->fill(collect($definition)->only(self::SYNCED_FIELDS)->all());

            if ($parentChanged) {
                $account->parent_id = $parentId;
            }

            $account->save();
        }

        $fields = array_merge($different, $parentChanged ? ['parent_id'] : [], $restored ? ['dipulihkan'] : []);

        return "Ubah {$definition['code']} {$definition['name']}: ".implode(', ', $fields);
    }

    private function warnings(Collection $definitions): array
    {
        return Account::whereNull('parent_id')
            ->whereNotIn('code', $definitions->pluck('code'))
            ->orderBy('code')
            ->get()
            ->map(fn (Account $account) => "Akun tingkat atas di luar master: {$account->code} {$account->name}")
            ->all();
    }
}
