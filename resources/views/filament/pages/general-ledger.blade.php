<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Native Filter Form -->
        <div class="bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium leading-6 text-gray-950 dark:text-white mb-2">
                        Pilih Akun
                    </label>
                    <select wire:model.live="account_id" class="block w-full rounded-lg border-0 py-2 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-800 dark:text-white dark:ring-white/20">
                        <option value="">-- Pilih Akun --</option>
                        @foreach($this->accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium leading-6 text-gray-950 dark:text-white mb-2">
                        Tanggal Mulai
                    </label>
                    <input type="date" wire:model.live="start_date" class="block w-full rounded-lg border-0 py-2 pl-3 pr-3 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-800 dark:text-white dark:ring-white/20">
                </div>

                <div>
                    <label class="block text-sm font-medium leading-6 text-gray-950 dark:text-white mb-2">
                        Tanggal Selesai
                    </label>
                    <input type="date" wire:model.live="end_date" class="block w-full rounded-lg border-0 py-2 pl-3 pr-3 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-800 dark:text-white dark:ring-white/20">
                </div>
            </div>
        </div>

        @if($this->ledgerEntries)
            @php
                $account = \App\Models\Account::find($account_id);
                $isDebitNormal = $account ? ($account->normal_balance === 'Debet') : true;
                $currentRunningBalance = $this->pageStartBalance;
            @endphp

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl p-5 ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Saldo Awal (Sebelum {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }})</span>
                        <div class="text-xl font-bold text-gray-900 dark:text-white mt-1 font-mono">
                            {{ $this->beginningBalance < 0 ? '(Rp ' . number_format(abs($this->beginningBalance), 2, ',', '.') . ')' : 'Rp ' . number_format($this->beginningBalance, 2, ',', '.') }}
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded-xl p-5 ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Saldo Berjalan (Per Halaman Ini)</span>
                        <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mt-1 font-mono">
                            {{ $this->pageStartBalance < 0 ? '(Rp ' . number_format(abs($this->pageStartBalance), 2, ',', '.') . ')' : 'Rp ' . number_format($this->pageStartBalance, 2, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/5 text-gray-900 dark:text-white font-semibold">
                            <tr>
                                <th class="px-6 py-4 whitespace-nowrap">Tanggal</th>
                                <th class="px-6 py-4 whitespace-nowrap">Ref</th>
                                <th class="px-6 py-4">Keterangan</th>
                                <th class="px-6 py-4 text-right whitespace-nowrap">Debit</th>
                                <th class="px-6 py-4 text-right whitespace-nowrap">Kredit</th>
                                <th class="px-6 py-4 text-right whitespace-nowrap">Saldo Berjalan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse($this->ledgerEntries as $entry)
                                @php
                                    $debit = (float)$entry->debit;
                                    $credit = (float)$entry->credit;
                                    
                                    // Update running balance based on normal balance rules
                                    if ($isDebitNormal) {
                                        $currentRunningBalance += ($debit - $credit);
                                    } else {
                                        $currentRunningBalance += ($credit - $debit);
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($entry->entry_date)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400 font-mono text-xs whitespace-nowrap">
                                        {{ $entry->reference }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-900 dark:text-gray-200">
                                        {{ $entry->description }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                        {{ $debit == 0 ? '-' : 'Rp ' . number_format($debit, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                        {{ $credit == 0 ? '-' : 'Rp ' . number_format($credit, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold font-mono {{ $currentRunningBalance >= 0 ? 'text-gray-900 dark:text-white' : 'text-rose-600 dark:text-rose-400' }} whitespace-nowrap">
                                        {{ $currentRunningBalance < 0 ? '(Rp ' . number_format(abs($currentRunningBalance), 2, ',', '.') . ')' : 'Rp ' . number_format($currentRunningBalance, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <x-heroicon-o-document-magnifying-glass class="mx-auto h-12 w-12 text-gray-300 mb-3" style="width: 48px; height: 48px;" />
                                        Tidak ada transaksi yang ditemukan pada periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($this->ledgerEntries->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-white/5 bg-gray-50 dark:bg-white/5">
                        {{ $this->ledgerEntries->links() }}
                    </div>
                @endif
            </div>
        @else
            <!-- Empty State Before Filtering -->
            <div class="bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl p-12 text-center">
                <x-heroicon-o-book-open class="mx-auto h-12 w-12 text-gray-400 mb-3" style="width: 48px; height: 48px;" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Buku Besar Kosong</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pilih akun dan sesuaikan rentang tanggal di atas untuk melihat detail mutasi.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
