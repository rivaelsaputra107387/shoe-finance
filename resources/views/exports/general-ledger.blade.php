@extends('exports.layout-excel', ['title' => 'BUKU BESAR', 'colspan' => 6])

@section('content')
    <tr>
        <td style="font-weight: bold; background-color: #f8fafc; color: #64748b;">AKUN</td>
        <td colspan="2" style="font-weight: bold; background-color: #f8fafc;">{{ $data['account']['code'] }} - {{ $data['account']['name'] }}</td>
        <td style="font-weight: bold; background-color: #f8fafc; color: #64748b;">SALDO NORMAL</td>
        <td colspan="2" style="font-weight: bold; background-color: #f8fafc;">{{ $data['account']['normal_balance'] }}</td>
    </tr>
    <tr><td colspan="6"></td></tr>

    <tr>
        <td style="font-weight: bold; background-color: #f1f5f9; text-align: center;">TANGGAL</td>
        <td style="font-weight: bold; background-color: #f1f5f9; text-align: center;">REFERENSI</td>
        <td style="font-weight: bold; background-color: #f1f5f9; text-align: center;">KETERANGAN</td>
        <td style="font-weight: bold; background-color: #f1f5f9; text-align: center;">DEBET</td>
        <td style="font-weight: bold; background-color: #f1f5f9; text-align: center;">KREDIT</td>
        <td style="font-weight: bold; background-color: #f1f5f9; text-align: center;">SALDO</td>
    </tr>
    
    <tr>
        <td colspan="3" style="font-weight: bold; font-style: italic; color: #64748b; text-align: right;">Saldo Awal</td>
        <td></td>
        <td></td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['beginning_balance'] }}</td>
    </tr>

    @forelse($data['transactions'] as $row)
        <tr>
            <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
            <td>{{ $row['reference'] }}</td>
            <td>{{ $row['description'] }}</td>
            <td data-format="#,##0.00">{{ $row['debit'] > 0 ? $row['debit'] : '' }}</td>
            <td data-format="#,##0.00">{{ $row['credit'] > 0 ? $row['credit'] : '' }}</td>
            <td data-format="#,##0.00">{{ $row['running_balance'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6" style="text-align: center; color: #94a3b8; font-style: italic;">Tidak ada transaksi pada periode ini</td>
        </tr>
    @endforelse

    <tr>
        <td colspan="3" style="font-weight: bold; text-align: right;">TOTAL MUTASI</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_debit'] }}</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_credit'] }}</td>
        <td></td>
    </tr>
    <tr>
        <td colspan="5" style="font-weight: bold; text-align: right; background-color: #e2e8f0;">SALDO AKHIR</td>
        <td style="font-weight: bold; background-color: #e2e8f0;" data-format="#,##0.00">{{ $data['ending_balance'] }}</td>
    </tr>
@endsection
