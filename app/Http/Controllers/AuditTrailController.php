<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditTrailController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['owner', 'finance'])) {
            abort(403, 'Akses ditolak: Hanya Owner & Finance yang dapat mengakses Log Audit Trail.');
        }

        $query = AuditTrail::with('user')->latest('created_at');

        if ($request->filled('table_name')) {
            $query->where('table_name', $request->table_name);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $auditTrails = $query->paginate(15)->withQueryString();

        $auditTrails->getCollection()->transform(function ($audit) {
            $tableName = $audit->table_name;
            $action = $audit->action;
            $oldData = $audit->old_data ?? [];
            $newData = $audit->new_data ?? [];
            $dataCombined = array_merge($oldData, $newData);

            // Table Human Name
            $tableLabel = match ($tableName) {
                'journal_entries'    => 'Jurnal Transaksi',
                'journal_entry_lines'=> 'Rincian Jurnal',
                'bank_mutations'     => 'Mutasi Bank',
                'fiscal_periods'     => 'Periode Akuntansi',
                'users'              => 'Pengguna Aplikasi',
                'accounts'           => 'Chart of Accounts (COA)',
                default              => ucfirst(str_replace('_', ' ', $tableName)),
            };

            // Action Human Name
            $actionLabel = match ($action) {
                'create'       => 'Tambah Baru',
                'update'       => 'Perubahan Data',
                'delete'       => 'Hapus Data',
                'close_period' => 'Tutup Buku',
                default        => strtoupper($action),
            };

            // Record context description / title
            $recordTitle = "#{$audit->record_id}";
            if (isset($dataCombined['reference'])) {
                $recordTitle = $dataCombined['reference'];
            } elseif (isset($dataCombined['name'])) {
                $recordTitle = $dataCombined['name'];
            } elseif (isset($dataCombined['code'])) {
                $recordTitle = $dataCombined['code'] . (isset($dataCombined['name']) ? ' - ' . $dataCombined['name'] : '');
            } elseif (isset($dataCombined['description'])) {
                $recordTitle = \Illuminate\Support\Str::limit($dataCombined['description'], 35);
            }

            // Summary narrative
            $userName = $audit->user?->name ?? 'Sistem';
            $narrative = match ($action) {
                'create'       => "{$userName} membuat {$tableLabel} ({$recordTitle})",
                'update'       => "{$userName} memperbarui {$tableLabel} ({$recordTitle})",
                'delete'       => "{$userName} menghapus {$tableLabel} ({$recordTitle})",
                'close_period' => "{$userName} melakukan Tutup Buku Periode ({$recordTitle})",
                default        => "{$userName} melakukan aksi {$actionLabel} pada {$tableLabel} ({$recordTitle})",
            };

            // Human readable field diffs
            $diffs = [];
            $allKeys = array_unique(array_merge(array_keys($oldData), array_keys($newData)));
            
            $fieldLabels = [
                'reference'        => 'Nomor Referensi',
                'description'      => 'Keterangan / Deskripsi',
                'entry_date'       => 'Tanggal Transaksi',
                'status'           => 'Status',
                'amount'           => 'Nominal (Rp)',
                'debit'            => 'Debet (Rp)',
                'credit'           => 'Kredit (Rp)',
                'name'             => 'Nama Pengguna / Item',
                'email'            => 'Email',
                'phone'            => 'No HP',
                'code'             => 'Kode Akun',
                'type'             => 'Tipe Akun',
                'normal_balance'   => 'Saldo Normal',
                'report_type'      => 'Tipe Laporan',
                'start_date'       => 'Tanggal Mulai',
                'end_date'         => 'Tanggal Selesai',
                'bank_source'      => 'Sumber Bank',
                'mutation_type'    => 'Tipe Mutasi',
                'fiscal_period_id' => 'ID Periode Akuntansi',
                'created_by'       => 'Dibuat Oleh ID',
            ];

            foreach ($allKeys as $key) {
                if (in_array($key, ['created_at', 'updated_at', 'deleted_at', 'remember_token', 'password', 'id'])) {
                    continue;
                }

                $oldVal = $oldData[$key] ?? null;
                $newVal = $newData[$key] ?? null;

                if ($oldVal === $newVal) {
                    continue;
                }

                $fieldLabel = $fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));

                $formatVal = function ($val, $k) {
                    if (is_null($val)) return '-';
                    if (is_bool($val)) return $val ? 'Ya' : 'Tidak';
                    if (in_array($k, ['amount', 'debit', 'credit']) && is_numeric($val)) {
                        return 'Rp ' . number_format((float)$val, 0, ',', '.');
                    }
                    if (is_array($val)) return json_encode($val);
                    return (string) $val;
                };

                $diffs[] = [
                    'field'     => $fieldLabel,
                    'old_value' => $formatVal($oldVal, $key),
                    'new_value' => $formatVal($newVal, $key),
                ];
            }

            $audit->table_label    = $tableLabel;
            $audit->action_label   = $actionLabel;
            $audit->record_title   = $recordTitle;
            $audit->narrative      = $narrative;
            $audit->formatted_diff = $diffs;

            return $audit;
        });

        return Inertia::render('Settings/AuditTrail', [
            'auditTrails'    => $auditTrails,
            'selectedTable'  => $request->input('table_name', ''),
            'selectedAction' => $request->input('action', ''),
        ]);
    }
}
