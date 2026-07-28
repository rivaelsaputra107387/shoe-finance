<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;

class BankMutationParserService
{
    /**
     * Map of Indonesian month names to English month names for Carbon parsing.
     */
    protected array $monthTranslations = [
        'januari'   => 'January',
        'februari'  => 'February',
        'maret'     => 'March',
        'april'     => 'April',
        'mei'       => 'May',
        'juni'      => 'June',
        'juli'      => 'July',
        'agustus'   => 'August',
        'september' => 'September',
        'oktober'   => 'October',
        'november'  => 'November',
        'desember'  => 'December',
        'jan'       => 'Jan',
        'feb'       => 'Feb',
        'mar'       => 'Mar',
        'apr'       => 'Apr',
        'mei'       => 'May',
        'jun'       => 'Jun',
        'jul'       => 'Jul',
        'agu'       => 'Aug',
        'sep'       => 'Sep',
        'okt'       => 'Oct',
        'nov'       => 'Nov',
        'des'       => 'Dec',
    ];

    /**
     * Parse a CSV bank mutation file with auto-header detection and flexible format handling.
     *
     * @param string $filePath Absolute path to the uploaded CSV file
     * @param string $forcedBankSource 'AUTO'|'BCA'|'MANDIRI'
     * @return array List of parsed mutation records
     */
    public function parse(string $filePath, string $forcedBankSource = 'AUTO'): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            return [];
        }

        // 1. Auto-detect delimiter (, or ; or \t)
        $delimiter = $this->detectDelimiter($lines);

        // 2. Scan lines to find the header row index and column map
        $headerInfo = $this->findHeaderRow($lines, $delimiter);
        if (!$headerInfo) {
            return [];
        }

        $headerRowIndex = $headerInfo['index'];
        $colMap         = $headerInfo['map'];

        $parsedRecords = [];

        // 3. Process data rows after header
        for ($i = $headerRowIndex + 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i], $delimiter, '"', '\\');

            // Skip completely empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Extract raw fields using column map
            $dateRaw  = isset($colMap['date']) && isset($row[$colMap['date']]) ? trim($row[$colMap['date']]) : null;
            $descRaw  = isset($colMap['desc']) && isset($row[$colMap['desc']]) ? trim($row[$colMap['desc']]) : '';

            if (!$dateRaw) {
                continue;
            }

            // Parse date
            $dateParsed = $this->parseDate($dateRaw);
            if (!$dateParsed) {
                continue; // Skip invalid date rows (e.g. footers/totals)
            }

            // Extract amounts and determine mutation type
            $amountInfo = $this->parseAmountAndType($row, $colMap);
            if (!$amountInfo || $amountInfo['amount'] <= 0) {
                continue;
            }

            // Determine bank source
            $bankSource = $forcedBankSource !== 'AUTO'
                ? $forcedBankSource
                : ($amountInfo['format'] === 'COMBINED' ? 'BCA' : 'MANDIRI');

            $parsedRecords[] = [
                'date'          => $dateParsed,
                'description'   => $descRaw ?: 'Mutasi Bank',
                'amount'        => $amountInfo['amount'],
                'mutation_type' => $amountInfo['type'],
                'bank_source'   => $bankSource,
            ];
        }

        return $parsedRecords;
    }

    /**
     * Detect CSV delimiter from sample lines.
     */
    protected function detectDelimiter(array $lines): string
    {
        $sample = implode("\n", array_slice($lines, 0, 10));
        $semicolonCount = substr_count($sample, ';');
        $commaCount     = substr_count($sample, ',');
        $tabCount       = substr_count($sample, "\t");

        if ($semicolonCount > $commaCount && $semicolonCount > $tabCount) {
            return ';';
        }
        if ($tabCount > $commaCount && $tabCount > $semicolonCount) {
            return "\t";
        }
        return ',';
    }

    /**
     * Scan lines to find the row containing header keywords.
     */
    protected function findHeaderRow(array $lines, string $delimiter): ?array
    {
        foreach ($lines as $index => $line) {
            $row = str_getcsv($line, $delimiter, '"', '\\');
            $normalizedRow = array_map(fn ($item) => strtolower(trim($item)), $row);

            $dateCol   = null;
            $descCol   = null;
            $jumlahCol = null;
            $kreditCol = null;
            $debitCol  = null;

            foreach ($normalizedRow as $colIdx => $val) {
                if (in_array($val, ['tgl', 'tanggal', 'date', 'trans date', 'posting date'])) {
                    $dateCol = $colIdx;
                } elseif (in_array($val, ['keterangan', 'deskripsi', 'description', 'uraian', 'remark'])) {
                    $descCol = $colIdx;
                } elseif (in_array($val, ['jumlah', 'amount', 'mutasi'])) {
                    $jumlahCol = $colIdx;
                } elseif (in_array($val, ['kredit', 'credit', 'cr'])) {
                    $kreditCol = $colIdx;
                } elseif (in_array($val, ['debit', 'db'])) {
                    $debitCol = $colIdx;
                }
            }

            // Valid header if it has date AND (description OR amount/credit/debit)
            if ($dateCol !== null && ($descCol !== null || $jumlahCol !== null || $kreditCol !== null || $debitCol !== null)) {
                return [
                    'index' => $index,
                    'map'   => [
                        'date'   => $dateCol,
                        'desc'   => $descCol,
                        'jumlah' => $jumlahCol,
                        'kredit' => $kreditCol,
                        'debit'  => $debitCol,
                    ],
                ];
            }
        }

        return null;
    }

    /**
     * Parse date string into Y-m-d format safely.
     */
    protected function parseDate(string $dateRaw): ?string
    {
        // Ignore hashes like "############"
        if (str_contains($dateRaw, '#') || strlen($dateRaw) < 6) {
            return null;
        }

        // Translate Indonesian month names to English
        $cleanStr = strtolower($dateRaw);
        foreach ($this->monthTranslations as $idMonth => $enMonth) {
            if (str_contains($cleanStr, $idMonth)) {
                $cleanStr = str_replace($idMonth, strtolower($enMonth), $cleanStr);
                break;
            }
        }

        // Try standard Carbon parsing
        try {
            return Carbon::parse($cleanStr)->format('Y-m-d');
        } catch (\Exception $e) {
            // Fallback for dd/mm/yyyy or dd-mm-yyyy
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/', $dateRaw, $matches)) {
                $day   = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year  = strlen($matches[3]) === 2 ? '20' . $matches[3] : $matches[3];
                return "{$year}-{$month}-{$day}";
            }
        }

        return null;
    }

    /**
     * Extract amount and type (IN / OUT).
     */
    protected function parseAmountAndType(array $row, array $colMap): ?array
    {
        // 1. Separate Credit & Debit columns (Mandiri style statement)
        if (isset($colMap['kredit']) || isset($colMap['debit'])) {
            $kreditStr = isset($colMap['kredit']) && isset($row[$colMap['kredit']]) ? $row[$colMap['kredit']] : '0';
            $debitStr  = isset($colMap['debit']) && isset($row[$colMap['debit']])   ? $row[$colMap['debit']]  : '0';

            $kreditVal = $this->cleanNumber($kreditStr);
            $debitVal  = $this->cleanNumber($debitStr);

            // Pada mutasi Mandiri: Kolom Debit = Uang Masuk (Saldo bertambah), Kolom Kredit = Uang Keluar (Saldo berkurang)
            if ($debitVal > 0) {
                return ['amount' => $debitVal, 'type' => 'IN', 'format' => 'SEPARATE'];
            } elseif ($kreditVal > 0) {
                return ['amount' => $kreditVal, 'type' => 'OUT', 'format' => 'SEPARATE'];
            }
        }

        // 2. Single Combined Amount column (BCA style)
        if (isset($colMap['jumlah']) && isset($row[$colMap['jumlah']])) {
            $jumlahStr = strtoupper($row[$colMap['jumlah']]);

            $isCredit = str_contains($jumlahStr, 'CR') || str_contains($jumlahStr, 'KREDIT');
            $amountVal = $this->cleanNumber($jumlahStr);

            if ($amountVal > 0) {
                return [
                    'amount' => $amountVal,
                    'type'   => $isCredit ? 'IN' : 'OUT',
                    'format' => 'COMBINED',
                ];
            }
        }

        return null;
    }

    /**
     * Clean numeric string: removes currency, commas, spaces, text.
     */
    protected function cleanNumber(string $val): float
    {
        // Remove currency symbols, CR, DB, spaces
        $clean = preg_replace('/[^\d\.\,]/i', '', $val);

        // If string contains both comma and dot (e.g., 1,234.56 or 1.234,56)
        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            if (strrpos($clean, '.') > strrpos($clean, ',')) {
                // Format: 1,234.56
                $clean = str_replace(',', '', $clean);
            } else {
                // Format: 1.234,56
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            }
        } else {
            // Replace comma with dot if comma is used as decimal separator
            $clean = str_replace(',', '.', $clean);
        }

        return (float) $clean;
    }
}
