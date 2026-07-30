@extends('exports.layout-excel', ['title' => 'LAPORAN ARUS KAS', 'colspan' => 2])

@section('content')
    @if(!$data['is_valid'])
        <tr>
            <td colspan="2" style="background-color: #fee2e2; color: #991b1b; text-align: center; font-weight: bold;">
                ⚠ Saldo kas akhir dari laporan ini tidak cocok dengan saldo aktual di buku besar.
            </td>
        </tr>
        <tr><td colspan="2"></td></tr>
    @endif

    <!-- OPERATING ACTIVITIES -->
    <tr>
        <td colspan="2" style="font-weight: bold; background-color: #eff6ff; color: #1e3a8a;">ARUS KAS DARI AKTIVITAS OPERASI</td>
    </tr>
    @forelse($data['operating'] as $item)
        <tr>
            <td>{{ $item['account_name'] }}</td>
            <td style="{{ $item['amount'] < 0 ? 'color: #b91c1c;' : '' }}" data-format="#,##0.00">{{ $item['amount'] }}</td>
        </tr>
    @empty
        <tr><td colspan="2" style="color: #999999;">Tidak ada transaksi</td></tr>
    @endforelse
    <tr>
        <td style="font-weight: bold;">Arus Kas Bersih dari Aktivitas Operasi</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_operating'] }}</td>
    </tr>
    <tr><td colspan="2"></td></tr>

    <!-- INVESTING ACTIVITIES -->
    <tr>
        <td colspan="2" style="font-weight: bold; background-color: #fffbeb; color: #78350f;">ARUS KAS DARI AKTIVITAS INVESTASI</td>
    </tr>
    @forelse($data['investing'] as $item)
        <tr>
            <td>{{ $item['account_name'] }}</td>
            <td style="{{ $item['amount'] < 0 ? 'color: #b91c1c;' : '' }}" data-format="#,##0.00">{{ $item['amount'] }}</td>
        </tr>
    @empty
        <tr><td colspan="2" style="color: #999999;">Tidak ada transaksi</td></tr>
    @endforelse
    <tr>
        <td style="font-weight: bold;">Arus Kas Bersih dari Aktivitas Investasi</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_investing'] }}</td>
    </tr>
    <tr><td colspan="2"></td></tr>

    <!-- FINANCING ACTIVITIES -->
    <tr>
        <td colspan="2" style="font-weight: bold; background-color: #faf5ff; color: #581c87;">ARUS KAS DARI AKTIVITAS PENDANAAN</td>
    </tr>
    @forelse($data['financing'] as $item)
        <tr>
            <td>{{ $item['account_name'] }}</td>
            <td style="{{ $item['amount'] < 0 ? 'color: #b91c1c;' : '' }}" data-format="#,##0.00">{{ $item['amount'] }}</td>
        </tr>
    @empty
        <tr><td colspan="2" style="color: #999999;">Tidak ada transaksi</td></tr>
    @endforelse
    <tr>
        <td style="font-weight: bold;">Arus Kas Bersih dari Aktivitas Pendanaan</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_financing'] }}</td>
    </tr>
    <tr><td colspan="2"></td></tr>

    <!-- SUMMARY -->
    <tr>
        <td style="background-color: #f5f3ff;">Kenaikan/(Penurunan) Kas Bersih</td>
        <td style="background-color: #f5f3ff; font-weight: bold;" data-format="#,##0.00">{{ $data['net_increase'] }}</td>
    </tr>
    <tr>
        <td style="background-color: #f5f3ff; color: #555555;">Saldo Kas Awal Periode</td>
        <td style="background-color: #f5f3ff; font-weight: bold;" data-format="#,##0.00">{{ $data['beginning_cash'] }}</td>
    </tr>
    <tr>
        <td style="background-color: #f5f3ff; font-weight: bold; color: #4338ca;">SALDO KAS AKHIR PERIODE</td>
        <td style="background-color: #f5f3ff; font-weight: bold; color: #4338ca;" data-format="#,##0.00">{{ $data['ending_cash'] }}</td>
    </tr>
@endsection
