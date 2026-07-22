<x-filament-panels::page>
    @if($activePeriodData)
        {{-- Active Period Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Periode Aktif</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="p-3 rounded-lg bg-primary-50 dark:bg-primary-950">
                    <p class="text-xs text-primary-600 dark:text-primary-400 uppercase font-semibold">Nama Periode</p>
                    <p class="text-lg font-bold text-primary-800 dark:text-primary-200">{{ $activePeriodData['name'] }}</p>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Mulai</p>
                    <p class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $activePeriodData['start_date'] }}</p>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Berakhir</p>
                    <p class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $activePeriodData['end_date'] }}</p>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Jumlah Jurnal</p>
                    <p class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $activePeriodData['journal_count'] }} transaksi</p>
                </div>
            </div>

            @if(!$showConfirmation)
                <div class="mt-6">
                    <x-filament::button wire:click="confirmClose" color="danger" icon="heroicon-o-lock-closed" size="lg">
                        Tutup Periode {{ $activePeriodData['name'] }}
                    </x-filament::button>
                </div>
            @endif
        </div>

        {{-- Confirmation Modal --}}
        @if($showConfirmation)
            <div class="mt-6 bg-danger-50 dark:bg-danger-950 rounded-xl border-2 border-danger-300 dark:border-danger-700 p-6">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-danger-500 flex-shrink-0 mt-1" />
                    <div>
                        <h3 class="text-lg font-bold text-danger-800 dark:text-danger-200">Konfirmasi Penutupan Periode</h3>
                        <p class="text-sm text-danger-700 dark:text-danger-300 mt-2">
                            Anda akan menutup periode <strong>{{ $activePeriodData['name'] }}</strong>. Aksi ini akan:
                        </p>
                        <ul class="mt-2 space-y-1 text-sm text-danger-700 dark:text-danger-300">
                            <li>📋 Generate jurnal penutup otomatis (menutup akun Pendapatan, Beban, dan Ikhtisar LR)</li>
                            <li>🔒 Mengunci semua jurnal di periode ini (tidak bisa diedit/dihapus)</li>
                            <li>📅 Membuat periode baru untuk bulan berikutnya</li>
                        </ul>
                        <p class="mt-3 text-sm font-bold text-danger-800 dark:text-danger-200">
                            ⚠️ Aksi ini TIDAK BISA dibatalkan. Pastikan semua transaksi sudah lengkap.
                        </p>
                        <div class="mt-4 flex gap-3">
                            <x-filament::button wire:click="closePeriod" color="danger" icon="heroicon-o-check">
                                Ya, Tutup Periode
                            </x-filament::button>
                            <x-filament::button wire:click="cancelClose" color="gray" icon="heroicon-o-x-mark">
                                Batal
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="p-6 text-center text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <x-heroicon-o-calendar class="w-12 h-12 mx-auto mb-2 text-gray-300" />
            <p>Tidak ada periode fiskal yang aktif.</p>
        </div>
    @endif

    {{-- Closing Result --}}
    @if(!empty($closingResult) && $closingResult['success'])
        <div class="mt-6 bg-success-50 dark:bg-success-950 rounded-xl border border-success-300 dark:border-success-700 p-6">
            <h3 class="text-lg font-bold text-success-800 dark:text-success-200 mb-3">✓ Penutupan Berhasil!</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-success-700 dark:text-success-300">Periode Ditutup:</span>
                    <span class="font-semibold text-success-800 dark:text-success-200">{{ $closingResult['closed_period'] }}</span>
                </div>
                <div>
                    <span class="text-success-700 dark:text-success-300">Periode Baru:</span>
                    <span class="font-semibold text-success-800 dark:text-success-200">{{ $closingResult['new_period'] }}</span>
                </div>
                <div>
                    <span class="text-success-700 dark:text-success-300">Jurnal Penutup Dibuat:</span>
                    <span class="font-semibold text-success-800 dark:text-success-200">{{ $closingResult['entries_count'] }} entri</span>
                </div>
                <div>
                    <span class="text-success-700 dark:text-success-300">Laba/Rugi Bersih:</span>
                    <span class="font-semibold {{ $closingResult['net_profit'] >= 0 ? 'text-success-800 dark:text-success-200' : 'text-danger-600' }}">
                        Rp {{ number_format($closingResult['net_profit'], 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
