<?php

namespace App\Console\Commands;

use App\Services\ReportSnapshotService;
use Illuminate\Console\Command;

class ReportCompareCommand extends Command
{
    private const MAX_SHOWN = 50;

    protected $signature = 'finlog:report-compare {before : File snapshot sebelum} {after : File snapshot sesudah}';

    protected $description = 'Bandingkan dua snapshot laporan. Gagal bila ada satu saja selisih.';

    public function handle(ReportSnapshotService $snapshots): int
    {
        $before = $this->readSnapshot($this->argument('before'));
        $after = $this->readSnapshot($this->argument('after'));

        if ($before === null || $after === null) {
            return self::FAILURE;
        }

        $differences = $snapshots->compare($before, $after);

        if ($differences === []) {
            $periods = implode(', ', array_keys($before['reports'] ?? []));
            $this->info("IDENTIK. Periode yang dibandingkan: {$periods}");

            return self::SUCCESS;
        }

        $this->error('BERBEDA: '.count($differences).' selisih.');

        foreach (array_slice($differences, 0, self::MAX_SHOWN) as $difference) {
            $this->line($difference);
        }

        if (count($differences) > self::MAX_SHOWN) {
            $this->line('... dan '.(count($differences) - self::MAX_SHOWN).' selisih lainnya');
        }

        return self::FAILURE;
    }

    private function readSnapshot(string $path): ?array
    {
        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return null;
        }

        $snapshot = json_decode((string) file_get_contents($path), true);

        if (! is_array($snapshot) || ! isset($snapshot['format_version'], $snapshot['reports'])) {
            $this->error("Bukan file snapshot yang valid: {$path}");

            return null;
        }

        return $snapshot;
    }
}
