<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Native Filter & Export Header -->
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

                @if(!empty($reportData))
                <div class="flex gap-3 mt-4 md:mt-0">
                    <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-o-document-arrow-down">
                        Export PDF
                    </x-filament::button>
                    <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-table-cells">
                        Export Excel
                    </x-filament::button>
                </div>
                @endif
            </div>
        </div>

        @if(!empty($reportData))
        <!-- Pure HTML/Tailwind Report Layout -->
        <div class="bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl overflow-hidden">
            <div class="p-6 md:p-10 border-b border-gray-200 dark:border-gray-800 text-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight uppercase">Laporan Laba Rugi</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Periode: {{ $reportData['period_name'] }}</p>
            </div>

            <div class="px-6 py-8 md:px-10">
                <table class="w-full text-sm text-left">
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        
                        <!-- PENDAPATAN -->
                        <tr>
                            <td colspan="2" class="py-3 font-bold text-gray-900 dark:text-white uppercase tracking-wider text-xs">
                                Pendapatan Usaha
                            </td>
                        </tr>
                        @foreach($reportData['revenue'] as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="py-2.5 pl-6 text-gray-600 dark:text-gray-300">{{ $item['name'] }}</td>
                            <td class="py-2.5 text-right font-mono text-gray-900 dark:text-white">
                                {{ $item['balance'] < 0 ? '(Rp ' . number_format(abs($item['balance']), 2, ',', '.') . ')' : 'Rp ' . number_format($item['balance'], 2, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-indigo-50/50 dark:bg-indigo-900/20">
                            <td class="py-4 pl-6 font-semibold text-indigo-900 dark:text-indigo-300">Total Pendapatan Usaha</td>
                            <td class="py-4 text-right font-bold font-mono text-indigo-900 dark:text-indigo-300 border-t border-indigo-200 dark:border-indigo-800">
                                {{ $reportData['total_revenue'] < 0 ? '(Rp ' . number_format(abs($reportData['total_revenue']), 2, ',', '.') . ')' : 'Rp ' . number_format($reportData['total_revenue'], 2, ',', '.') }}
                            </td>
                        </tr>

                        <!-- HPP -->
                        <tr>
                            <td colspan="2" class="py-3 mt-4 font-bold text-gray-900 dark:text-white uppercase tracking-wider text-xs">
                                Harga Pokok Penjualan (HPP)
                            </td>
                        </tr>
                        @foreach($reportData['hpp'] as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="py-2.5 pl-6 text-gray-600 dark:text-gray-300">{{ $item['name'] }}</td>
                            <td class="py-2.5 text-right font-mono text-gray-900 dark:text-white">
                                {{ $item['balance'] < 0 ? '(Rp ' . number_format(abs($item['balance']), 2, ',', '.') . ')' : 'Rp ' . number_format($item['balance'], 2, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-rose-50/50 dark:bg-rose-900/20">
                            <td class="py-4 pl-6 font-semibold text-rose-900 dark:text-rose-300">Total Harga Pokok Penjualan</td>
                            <td class="py-4 text-right font-bold font-mono text-rose-900 dark:text-rose-300 border-t border-rose-200 dark:border-rose-800">
                                {{ $reportData['total_hpp'] < 0 ? '(Rp ' . number_format(abs($reportData['total_hpp']), 2, ',', '.') . ')' : 'Rp ' . number_format($reportData['total_hpp'], 2, ',', '.') }}
                            </td>
                        </tr>

                        <!-- LABA KOTOR -->
                        <tr class="bg-gray-100 dark:bg-white/5">
                            <td class="py-5 px-4 font-bold text-gray-900 dark:text-white text-base">LABA KOTOR</td>
                            <td class="py-5 px-4 text-right font-black font-mono text-lg {{ $reportData['gross_profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $reportData['gross_profit'] < 0 ? '(Rp ' . number_format(abs($reportData['gross_profit']), 2, ',', '.') . ')' : 'Rp ' . number_format($reportData['gross_profit'], 2, ',', '.') }}
                            </td>
                        </tr>

                        <!-- BEBAN OPERASIONAL -->
                        <tr>
                            <td colspan="2" class="py-3 mt-4 font-bold text-gray-900 dark:text-white uppercase tracking-wider text-xs">
                                Beban Operasional
                            </td>
                        </tr>
                        @foreach($reportData['operational_expenses'] as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="py-2.5 pl-6 text-gray-600 dark:text-gray-300">{{ $item['name'] }}</td>
                            <td class="py-2.5 text-right font-mono text-gray-900 dark:text-white">
                                {{ $item['balance'] < 0 ? '(Rp ' . number_format(abs($item['balance']), 2, ',', '.') . ')' : 'Rp ' . number_format($item['balance'], 2, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-rose-50/50 dark:bg-rose-900/20">
                            <td class="py-4 pl-6 font-semibold text-rose-900 dark:text-rose-300">Total Beban Operasional</td>
                            <td class="py-4 text-right font-bold font-mono text-rose-900 dark:text-rose-300 border-t border-rose-200 dark:border-rose-800">
                                {{ $reportData['total_operational_expenses'] < 0 ? '(Rp ' . number_format(abs($reportData['total_operational_expenses']), 2, ',', '.') . ')' : 'Rp ' . number_format($reportData['total_operational_expenses'], 2, ',', '.') }}
                            </td>
                        </tr>

                        <!-- LABA OPERASI -->
                        <tr class="bg-gray-100 dark:bg-white/5">
                            <td class="py-5 px-4 font-bold text-gray-900 dark:text-white text-base">LABA OPERASI</td>
                            <td class="py-5 px-4 text-right font-black font-mono text-lg {{ $reportData['operating_profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $reportData['operating_profit'] < 0 ? '(Rp ' . number_format(abs($reportData['operating_profit']), 2, ',', '.') . ')' : 'Rp ' . number_format($reportData['operating_profit'], 2, ',', '.') }}
                            </td>
                        </tr>

                        <!-- PENDAPATAN LAIN -->
                        @if(!empty($reportData['other_revenue']))
                        <tr>
                            <td colspan="2" class="py-3 mt-4 font-bold text-gray-900 dark:text-white uppercase tracking-wider text-xs">
                                Pendapatan Lain-lain
                            </td>
                        </tr>
                        @foreach($reportData['other_revenue'] as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="py-2.5 pl-6 text-gray-600 dark:text-gray-300">{{ $item['name'] }}</td>
                            <td class="py-2.5 text-right font-mono text-gray-900 dark:text-white">
                                {{ $item['balance'] < 0 ? '(Rp ' . number_format(abs($item['balance']), 2, ',', '.') . ')' : 'Rp ' . number_format($item['balance'], 2, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-emerald-50/50 dark:bg-emerald-900/20">
                            <td class="py-4 pl-6 font-semibold text-emerald-900 dark:text-emerald-300">Total Pendapatan Lain-lain</td>
                            <td class="py-4 text-right font-bold font-mono text-emerald-900 dark:text-emerald-300 border-t border-emerald-200 dark:border-emerald-800">
                                {{ $reportData['total_other_revenue'] < 0 ? '(Rp ' . number_format(abs($reportData['total_other_revenue']), 2, ',', '.') . ')' : 'Rp ' . number_format($reportData['total_other_revenue'], 2, ',', '.') }}
                            </td>
                        </tr>
                        @endif

                        <!-- BEBAN LAIN -->
                        @if(!empty($reportData['other_expenses']))
                        <tr>
                            <td colspan="2" class="py-3 mt-4 font-bold text-gray-900 dark:text-white uppercase tracking-wider text-xs">
                                Beban Lain-lain
                            </td>
                        </tr>
                        @foreach($reportData['other_expenses'] as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="py-2.5 pl-6 text-gray-600 dark:text-gray-300">{{ $item['name'] }}</td>
                            <td class="py-2.5 text-right font-mono text-gray-900 dark:text-white">
                                {{ $item['balance'] < 0 ? '(Rp ' . number_format(abs($item['balance']), 2, ',', '.') . ')' : 'Rp ' . number_format($item['balance'], 2, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-rose-50/50 dark:bg-rose-900/20">
                            <td class="py-4 pl-6 font-semibold text-rose-900 dark:text-rose-300">Total Beban Lain-lain</td>
                            <td class="py-4 text-right font-bold font-mono text-rose-900 dark:text-rose-300 border-t border-rose-200 dark:border-rose-800">
                                {{ $reportData['total_other_expenses'] < 0 ? '(Rp ' . number_format(abs($reportData['total_other_expenses']), 2, ',', '.') . ')' : 'Rp ' . number_format($reportData['total_other_expenses'], 2, ',', '.') }}
                            </td>
                        </tr>
                        @endif

                        <!-- BEBAN ADMIN -->
                        @if(!empty($reportData['admin_expenses']))
                        <tr>
                            <td colspan="2" class="py-3 mt-4 font-bold text-gray-900 dark:text-white uppercase tracking-wider text-xs">
                                Beban Administrasi & Pajak
                            </td>
                        </tr>
                        @foreach($reportData['admin_expenses'] as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="py-2.5 pl-6 text-gray-600 dark:text-gray-300">{{ $item['name'] }}</td>
                            <td class="py-2.5 text-right font-mono text-gray-900 dark:text-white">
                                {{ $item['balance'] < 0 ? '(Rp ' . number_format(abs($item['balance']), 2, ',', '.') . ')' : 'Rp ' . number_format($item['balance'], 2, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-rose-50/50 dark:bg-rose-900/20">
                            <td class="py-4 pl-6 font-semibold text-rose-900 dark:text-rose-300">Total Beban Administrasi & Pajak</td>
                            <td class="py-4 text-right font-bold font-mono text-rose-900 dark:text-rose-300 border-t border-rose-200 dark:border-rose-800">
                                {{ $reportData['total_admin_expenses'] < 0 ? '(Rp ' . number_format(abs($reportData['total_admin_expenses']), 2, ',', '.') . ')' : 'Rp ' . number_format($reportData['total_admin_expenses'], 2, ',', '.') }}
                            </td>
                        </tr>
                        @endif

                        <!-- LABA BERSIH -->
                        <tr class="bg-gray-900 dark:bg-black border-t-4 border-emerald-500">
                            <td class="py-6 px-4 font-bold text-white text-lg">LABA / RUGI BERSIH</td>
                            <td class="py-6 px-4 text-right font-black font-mono text-2xl {{ $reportData['net_profit'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $reportData['net_profit'] < 0 ? '(Rp ' . number_format(abs($reportData['net_profit']), 2, ',', '.') . ')' : 'Rp ' . number_format($reportData['net_profit'], 2, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl p-12 text-center">
            <x-heroicon-o-document-magnifying-glass class="mx-auto h-12 w-12 text-gray-400" style="width: 48px; height: 48px;" />
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Pilih Periode Laporan</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Silakan pilih periode akuntansi pada form di atas untuk menampilkan Laporan Laba Rugi.</p>
        </div>
        @endif
    </div>
</x-filament-panels::page>
