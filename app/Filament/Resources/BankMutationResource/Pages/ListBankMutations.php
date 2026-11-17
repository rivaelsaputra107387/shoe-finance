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
                            'AUTO' => 'Otomatis Deteksi (Rekomendasi - BCA & Mandiri)',
                            'BCA' => 'BCA (Terdapat kolom mutasi gabungan)',
                            'MANDIRI' => 'Mandiri (Terdapat kolom Debit dan Kredit terpisah)',
                        ])
                        ->default('AUTO')
                        ->required(),
                    Forms\Components\FileUpload::make('file')
                        ->label('File CSV / Excel Export')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'text/x-csv',
                            'application/csv',
                            'application/x-csv',
                            'text/comma-separated-values',
                            'text/x-comma-separated-values',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/octet-stream',
                        ])
                        ->required()
                        ->helperText('Unggah file CSV / TXT / Excel ekspor mutasi internet banking BCA/Mandiri (tidak perlu menghapus baris judul di atasnya).'),
                ])
                ->action(function (array $data) {
                    try {
                        $filePath = Storage::disk('public')->path($data['file']);
                        $bankSource = $data['bank_source'] ?? 'AUTO';

                        $parser = app(\App\Services\BankMutationParserService::class);
                        $records = $parser->parse($filePath, $bankSource);

                        if (empty($records)) {
                            Notification::make()
                                ->title('Tidak ada data mutasi yang ditemukan')
                                ->body('Pastikan file CSV memuat kolom tanggal, deskripsi, dan nominal/debit/kredit.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $count = 0;
                        foreach ($records as $item) {
                            BankMutation::create([
                                'date'          => $item['date'],
                                'description'   => $item['description'],
                                'amount'        => $item['amount'],
                                'bank_source'   => $item['bank_source'],
                                'mutation_type' => $item['mutation_type'],
                                'status'        => 'pending',
                                'uploaded_by'   => auth()->id(),
                            ]);
                            $count++;
                        }

                        Notification::make()
                            ->title("Berhasil mengimpor $count transaksi mutasi!")
                            ->body("File berhasil diproses secara otomatis.")
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
