<?php

namespace App\Filament\Widgets;

use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActivePeriodWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $period = FiscalPeriod::active();

        if (!$period) {
            return [
                Stat::make('Status Sistem', 'TUTUP')
                    ->description('Tidak ada periode aktif')
                    ->color('danger'),
            ];
        }

        $query = JournalEntry::forPeriod($period->id)->posted();
        if (auth()->user()?->hasRole('staff')) {
            $query->where('created_by', auth()->id());
        }
        $journalCount = $query->count();

        return [
            Stat::make('Periode Aktif', $period->name)
                ->description("Rentang: {$period->start_date->format('d M')} - {$period->end_date->format('d M Y')}")
                ->color('success'),
            Stat::make('Jumlah Transaksi', $journalCount . ' entri')
                ->description('Total jurnal terinput')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('info'),
            Stat::make('Status Buku', 'BUKA')
                ->description('Penerimaan transaksi aktif')
                ->descriptionIcon('heroicon-m-lock-open')
                ->color('success'),
        ];
    }
}
