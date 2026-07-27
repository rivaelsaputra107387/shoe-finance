<?php

namespace App\Filament\Resources\BankMutationResource\Pages;

use App\Filament\Resources\BankMutationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use App\Models\BankMutation;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ListBankMutations extends ListRecords
{
    protected static string $resource = BankMutationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Manual')
                ->icon('heroicon-o-plus')
                ->using(function (array $data, string $model): \Illuminate\Database\Eloquent\Model {
                    $mutation = $model::create(array_merge($data, [
                        'status' => 'pending', 
                    ]));
                    return $mutation;
                })
                ->after(function (\App\Models\BankMutation $record) {
                    $service = app(\App\Services\BankMutationService::class);
                    $service->draftJournalEntry($record, auth()->id());
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Berhasil!')
                        ->body('Mutasi ditambahkan dan Draft Jurnal dibuat.')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('import_csv')
                ->label('Import CSV (BCA/Mandiri)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('bank_source')
                        ->label('Pilih Format Bank')
                        ->options([
                            'BCA' => 'BCA (Terdapat kolom mutasi gabungan)',
                            'MANDIRI' => 'Mandiri (Terdapat kolom Debit dan Kredit terpisah)',
                        ])
                        ->required(),
                    Forms\Components\FileUpload::make('file')
                        ->label('File CSV')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->required()
                        ->helperText('Pastikan Anda meng-export mutasi internet banking dalam format CSV.'),
                ])
                ->action(function (array $data) {
                    try {
                        $filePath = Storage::disk('public')->path($data['file']);
                        $bankSource = $data['bank_source'];
                        
                        $file = fopen($filePath, 'r');
                        $headerSkipped = false;
                        
                        $count = 0;
                        while (($row = fgetcsv($file, 1000, ',')) !== false) {
                            // BCA biasanya header ada di baris 5, atau kita abaikan row kosong.
                            // Untuk MVP, kita asumsikan CSV yang diupload sudah dibersihkan (baris pertama header).
                            if (!$headerSkipped) {
                                $headerSkipped = true;
                                continue;
                            }

                            // Hindari baris kosong
                            if (empty(array_filter($row))) continue;

                            if ($bankSource === 'BCA') {
                                // BCA CSV Real Structure:
                                // 0: tgl (DD/MM/YYYY), 1: Keterangan, 2: Cabang, 3: Jumlah, 4: Saldo
                                $dateRaw = $row[0] ?? null;
                                $desc = $row[1] ?? '';
                                $jumlahStr = $row[3] ?? '';
                                
                                if (!$dateRaw || strlen($dateRaw) < 8) continue;

                                $isCredit = str_contains(strtoupper($jumlahStr), 'CR');
                                // Hapus koma pemisah ribuan, hapus 'CR' dan 'DB', dan trim spasi
                                $amountRaw = str_replace([',', 'CR', 'DB', 'cr', 'db', ' '], '', $jumlahStr);
                                $mutation_type = $isCredit ? 'IN' : 'OUT'; // BCA CR = Uang Masuk, DB/None = Keluar

                                BankMutation::create([
                                    'date' => Carbon::createFromFormat('d/m/Y', $dateRaw)->format('Y-m-d'),
                                    'description' => $desc,
                                    'amount' => (float)$amountRaw,
                                    'bank_source' => 'BCA',
                                    'mutation_type' => $mutation_type,
                                    'status' => 'pending',
                                    'uploaded_by' => auth()->id(),
                                ]);
                                $count++;

                            } else if ($bankSource === 'MANDIRI') {
                                // Mandiri CSV Real Structure:
                                // 0: Tanggal (DD Mmm YYYY), 1: Deskripsi, 2: Kredit, 3: Debit, 4: Saldo
                                $dateRaw = $row[0] ?? null;
                                $desc = $row[1] ?? '';
                                $creditRaw = str_replace([','], '', $row[2] ?? '0');
                                $debitRaw = str_replace([','], '', $row[3] ?? '0');
                                
                                if (!$dateRaw || strlen($dateRaw) < 8) continue;

                                // Uang masuk (Kredit bank) = Mutasi IN
                                $isCredit = ((float)$creditRaw > 0);

                                if ($isCredit) {
                                    $amount = (float)$creditRaw;
                                    $mutation_type = 'IN'; 
                                } else {
                                    $amount = (float)$debitRaw;
                                    $mutation_type = 'OUT';
                                }

                                // Mandiri date format: "1 July 2026" or "01 Jul 2026"
                                try {
                                    $dateParsed = Carbon::parse($dateRaw)->format('Y-m-d');
                                } catch (\Exception $e) {
                                    // Fallback to exactly d/m/Y if parsing fails
                                    $dateParsed = Carbon::createFromFormat('d/m/Y', $dateRaw)->format('Y-m-d');
                                }

                                BankMutation::create([
                                    'date' => $dateParsed,
                                    'description' => $desc,
                                    'amount' => $amount,
                                    'bank_source' => 'MANDIRI',
                                    'mutation_type' => $mutation_type,
                                    'status' => 'pending',
                                    'uploaded_by' => auth()->id(),
                                ]);
                                $count++;
                            }
                        }
                        
                        fclose($file);
                        
                        Notification::make()
                            ->title("Berhasil mengimpor $count baris mutasi!")
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal mengimpor file')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
