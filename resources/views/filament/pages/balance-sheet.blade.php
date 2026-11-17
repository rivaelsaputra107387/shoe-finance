<x-filament-panels::page>
    <div class="space-y-6">
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
                <x-filament::button wire:click="exportPdf" color="success" icon="heroicon-o-document-arrow-down">
                    Export PDF
                </x-filament::button>
            </div>
            @endif
        </div>
    </div>

    @if(!empty($reportData))
        <div class="mt-6 w-full">
            {{ $this->reportInfolist }}
        </div>
    @endif
    </div>
</x-filament-panels::page>
