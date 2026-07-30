@extends('reports.layout-pdf')

@section('content')
    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 15%; font-weight: bold; color: #64748b; font-size: 10px;">AKUN</td>
                <td style="width: 35%; font-weight: bold; font-size: 12px;">{{ $data['account']['code'] }} - {{ $data['account']['name'] }}</td>
                <td style="width: 15%; font-weight: bold; color: #64748b; font-size: 10px;">SALDO NORMAL</td>
                <td style="width: 35%; font-weight: bold; font-size: 12px;">{{ $data['account']['normal_balance'] }}</td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th class="text-left" style="width: 12%;">TANGGAL</th>
                <th class="text-left" style="width: 15%;">REFERENSI</th>
                <th class="text-left" style="width: 33%;">KETERANGAN</th>
                <th class="text-right" style="width: 13%;">DEBET</th>
                <th class="text-right" style="width: 13%;">KREDIT</th>
                <th class="text-right" style="width: 14%;">SALDO</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="3" class="font-bold font-italic" style="color: #64748b;">Saldo Awal</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right font-mono font-bold">Rp {{ number_format($data['beginning_balance'], 2, ',', '.') }}</td>
            </tr>
            
            @forelse($data['transactions'] as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                    <td class="font-mono" style="font-size: 9px;">{{ $row['reference'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td class="text-right font-mono">{{ $row['debit'] > 0 ? number_format($row['debit'], 2, ',', '.') : '-' }}</td>
                    <td class="text-right font-mono">{{ $row['credit'] > 0 ? number_format($row['credit'], 2, ',', '.') : '-' }}</td>
                    <td class="text-right font-mono">{{ number_format($row['running_balance'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="font-style: italic; color: #94a3b8; padding: 15px;">Tidak ada transaksi pada periode ini</td>
                </tr>
            @endforelse
            
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL MUTASI</td>
                <td class="text-right font-mono">Rp {{ number_format($data['total_debit'], 2, ',', '.') }}</td>
                <td class="text-right font-mono">Rp {{ number_format($data['total_credit'], 2, ',', '.') }}</td>
                <td class="text-right"></td>
            </tr>
            <tr class="grand-total">
                <td colspan="5" class="text-right">SALDO AKHIR</td>
                <td class="text-right font-mono">Rp {{ number_format($data['ending_balance'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
@endsection
