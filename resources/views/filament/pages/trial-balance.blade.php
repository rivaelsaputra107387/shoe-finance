<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Native Filter -->
        <div class="bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl p-6">
            <div class="flex flex-col md:flex-row items-end gap-4">
                <div class="flex-1 w-full md:max-w-xs">
                    <label class="block text-sm font-medium leading-6 text-gray-950 dark:text-white mb-2">
                        Pilih Periode Laporan
                    </label>
                    <select wire:model.live="fiscal_period_id" class="block w-full rounded-lg border-0 py-2 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-800 dark:text-white dark:ring-white/20">
                        <option value="">-- Pilih Periode --</option>
                        @foreach($this->availablePeriods as $period)
                            <option value="{{ $period->id }}">{{ $period->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @if($fiscal_period_id)
            <div class="space-y-6">
            {{-- Balance Status Banner --}}
            <div class="p-4 rounded-xl border {{ $isBalanced ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-300 dark:border-rose-700 text-rose-800 dark:text-rose-200' }} text-sm font-semibold shadow-sm">
                @if($isBalanced)
                    ✓ Neraca Lajur Seimbang
                @else
                    ⚠ PERINGATAN: Neraca Lajur Tidak Seimbang! Selisih: Rp {{ number_format(abs($totalDebit - $totalCredit), 2, ',', '.') }}
                @endif
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
                    <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Total Akumulasi Debet</span>
                    <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1 font-mono">
                        Rp {{ number_format($totalDebit, 2, ',', '.') }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
                    <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Total Akumulasi Kredit</span>
                    <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                        Rp {{ number_format($totalCredit, 2, ',', '.') }}
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                {{ $this->table }}
            </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
