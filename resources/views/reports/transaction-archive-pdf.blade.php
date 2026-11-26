@extends('reports.layout-pdf')

@section('styles')
<style>
    .badge-in {
        display: inline-block;
        padding: 2px 6px;
        background-color: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
        border-radius: 3px;
        font-size: 8px;
        font-weight: bold;
    }
    .badge-out {
        display: inline-block;
        padding: 2px 6px;
        background-color: #fff1f2;
        color: #be123c;
        border: 1px solid #fecdd3;
        border-radius: 3px;
        font-size: 8px;
        font-weight: bold;
    }
    .badge-bank {
        display: inline-block;
        padding: 2px 6px;
        background-color: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
        border-radius: 3px;
        font-size: 8px;
        font-weight: bold;
    }
    .summary-card-table {
        width: 100%;
        margin-bottom: 15px;
        border-collapse: collapse;
    }
    .summary-card {
        padding: 8px 10px;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
        border-radius: 4px;
    }
    .summary-label {
        font-size: 8.5px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: bold;
        letter-spacing: 0.5px;
    }
    .summary-value {
        font-size: 12px;
        font-weight: bold;
        color: #0f172a;
        margin-top: 2px;
        font-family: 'Courier New', Courier, monospace;
    }
    .archive-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
    }
    .archive-table th {
        background-color: #f1f5f9;
        color: #334155;
        font-weight: bold;
        font-size: 8.5px;
        text-transform: uppercase;
        padding: 6px 6px;
        border: 1px solid #cbd5e1;
    }
    .archive-table td {
        padding: 5px 6px;
        border: 1px solid #e2e8f0;
        vertical-align: middle;
    }
    .archive-table tr:nth-child(even) td {
        background-color: #fafbfc;
    }
    .account-code {
        color: #64748b;
        font-size: 8px;
        font-family: 'Courier New', Courier, monospace;
        margin-top: 1px;
    }
    .ref-badge {
        font-family: 'Courier New', Courier, monospace;
        font-weight: bold;
        color: #047857;
        font-size: 8.5px;
    }
</style>
@endsection

@section('content')
    <!-- Summary Stat Cards -->
    <table class="summary-card-table">
        <tr>
            <td style="width: 25%; padding-right: 5px;">
                <div class="summary-card">
                    <div class="summary-label">Total Transaksi</div>
                    <div class="summary-value">{{ number_format($mutations->count(), 0, ',', '.') }} Data</div>
                </div>
            </td>
            <td style="width: 25%; padding-right: 5px; padding-left: 5px;">
                <div class="summary-card" style="border-left: 3px solid #10b981;">
                    <div class="summary-label">Total Masuk (IN)</div>
                    <div class="summary-value" style="color: #047857;">Rp {{ number_format($totalIn, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%; padding-right: 5px; padding-left: 5px;">
                <div class="summary-card" style="border-left: 3px solid #f43f5e;">
                    <div class="summary-label">Total Keluar (OUT)</div>
                    <div class="summary-value" style="color: #be123c;">Rp {{ number_format($totalOut, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%; padding-left: 5px;">
                <div class="summary-card" style="border-left: 3px solid #6366f1;">
                    <div class="summary-label">Net (Selisih Kas)</div>
                    <div class="summary-value" style="color: {{ $net >= 0 ? '#047857' : '#be123c' }};">
                        Rp {{ number_format($net, 0, ',', '.') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Data Table -->
    <table class="archive-table">
        <thead>
            <tr>
                <th style="width: 4%; text-align: center;">NO</th>
                <th style="width: 10%; text-align: left;">TANGGAL</th>
                <th style="width: 33%; text-align: left;">DESKRIPSI</th>
                <th style="width: 8%; text-align: center;">BANK</th>
                <th style="width: 7%; text-align: center;">TIPE</th>
                <th style="width: 13%; text-align: right;">NOMINAL</th>
                <th style="width: 14%; text-align: left;">NO. JURNAL & AKUN</th>
                <th style="width: 11%; text-align: left;">DIARSIPI OLEH</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mutations as $index => $item)
                <tr>
                    <td style="text-align: center; color: #64748b;">{{ $index + 1 }}</td>
                    <td style="white-space: nowrap; font-family: 'Courier New', Courier, monospace;">
                        {{ \Carbon\Carbon::parse($item->date)->format('d M Y') }}
                    </td>
                    <td>{{ $item->description }}</td>
                    <td style="text-align: center;">
                        <span class="badge-bank">{{ $item->bank_source }}</span>
                    </td>
                    <td style="text-align: center;">
                        @if($item->mutation_type === 'IN')
                            <span class="badge-in">MASUK</span>
                        @else
                            <span class="badge-out">KELUAR</span>
                        @endif
                    </td>
                    <td style="text-align: right; font-family: 'Courier New', Courier, monospace; font-weight: bold;">
                        Rp {{ number_format($item->amount, 0, ',', '.') }}
                    </td>
                    <td>
                        @if($item->journalEntry)
                            <div class="ref-badge">{{ $item->journalEntry->reference }}</div>
                            @if($item->journalEntry->lines && $item->journalEntry->lines->count() > 0)
                                <div class="account-code">
                                    {{ $item->journalEntry->lines->pluck('account.code')->filter()->unique()->implode(', ') }}
                                </div>
                            @endif
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                    <td style="color: #475569; font-size: 8px;">
                        {{ $item->poster?->name ?? $item->uploader?->name ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 25px; color: #94a3b8; font-style: italic;">
                        Tidak ada arsip transaksi yang sesuai dengan filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($mutations->isNotEmpty())
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold; border-top: 2px solid #94a3b8;">
                    <td colspan="5" style="text-align: right; padding: 7px;">TOTAL ({{ $mutations->count() }} Transaksi):</td>
                    <td style="text-align: right; font-family: 'Courier New', Courier, monospace; font-size: 9.5px; color: #0f172a; padding: 7px;">
                        Rp {{ number_format($mutations->sum('amount'), 0, ',', '.') }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
