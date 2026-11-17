<?php

namespace App\Console\Commands;

use App\Services\ReportSnapshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportSnapshotCommand extends Command
{
    protected $signature = 'finlog:report-snapshot
        {--output= : Path file JSON tujuan, di luar repo atau di dalam folder storage}
        {--period=* : Tanggal mulai periode (YYYY-MM-DD). Kosong berarti semua periode yang punya jurnal}
        {--force : Timpa file yang sudah ada}';

    protected $description = 'Simpan angka laporan keuangan ke JSON. Hanya membaca database.';

    public function handle(ReportSnapshotService $snapshots): int
    {
        $output = $this->option('output');

        if (! $output) {
            $this->error('Isi --output dengan path file tujuan.');

            return self::FAILURE;
        }

        if ($this->isUnsafeLocation($output)) {
            $this->error('Snapshot berisi data keuangan. Simpan di luar repo atau di dalam folder storage.');

            return self::FAILURE;
        }

        if (file_exists($output) && ! $this->option('force')) {
            $this->error("File sudah ada: {$output}. Pakai --force untuk menimpa.");

            return self::FAILURE;
        }

        $connection = DB::connection();
        $this->info("Sumber data: {$connection->getConfig('host')} / {$connection->getDatabaseName()}");

        try {
            $snapshot = $snapshots->build($this->option('period'));
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // Written fresh so the mode applies, because the file holds financial data.
        if (file_exists($output)) {
            unlink($output);
        }
        touch($output);
        chmod($output, 0600);
        file_put_contents($output, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $periods = array_keys($snapshot['reports']);
        $this->info('Periode: '.($periods === [] ? '(tidak ada yang berisi jurnal)' : implode(', ', $periods)));
        $this->info("Snapshot tersimpan: {$output}");

        return self::SUCCESS;
    }

    private function isUnsafeLocation(string $output): bool
    {
        $directory = realpath(dirname($output));

        if ($directory === false) {
            return true;
        }

        $root = base_path();

        return ($directory === $root || str_starts_with($directory, $root.DIRECTORY_SEPARATOR))
            && ! str_starts_with($directory, storage_path());
    }
}
