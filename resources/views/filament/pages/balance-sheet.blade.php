<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}
        <div class="flex gap-2" style="margin-top: 24px;">
            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">Tampilkan Neraca</x-filament::button>
            @if(!empty($reportData))
                <x-filament::button wire:click="exportPdf" color="success" icon="heroicon-o-document-arrow-down">Export PDF</x-filament::button>
            @endif
        </div>
    </form>

    @if(!empty($reportData))
        <div class="mt-6">
            {{ $this->reportInfolist }}
        </div>
    @endif
</x-filament-panels::page>
