@extends('exports.layout-excel', ['title' => 'LAPORAN PERUBAHAN EKUITAS', 'colspan' => 2])

@section('content')
    {{-- Modal awal = Modal Disetor + Laba Ditahan per awal periode; setiap baris angka ikut dijumlah. --}}
    <tr>
        <td colspan="2" style="font-weight: bold;">MODAL AWAL PERIODE</td>
    </tr>
    <tr>
        <td>{{ $data['modal_account_name'] }}</td>
        <td data-format="#,##0.00">{{ round($data['beginning_capital'] - $data['retained_earnings'], 2) }}</td>
    </tr>
    <tr>
        <td>Laba Ditahan</td>
        <td data-format="#,##0.00">{{ $data['retained_earnings'] }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;">Total Modal Awal Periode</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['beginning_capital'] }}</td>
    </tr>
    <tr><td colspan="2"></td></tr>

    <tr>
        <td colspan="2" style="font-weight: bold;">PERUBAHAN SELAMA PERIODE</td>
    </tr>
    <tr>
        <td>Setoran {{ $data['modal_account_name'] }} (+)</td>
        <td data-format="#,##0.00">{{ $data['additional_capital'] }}</td>
    </tr>
    <tr>
        <td>{{ $data['net_profit'] >= 0 ? 'Laba Bersih Periode Berjalan (+)' : 'Rugi Bersih Periode Berjalan (-)' }}</td>
        <td style="color: {{ $data['net_profit'] >= 0 ? '#047857' : '#b91c1c' }};" data-format="#,##0.00">{{ $data['net_profit'] }}</td>
    </tr>
    <tr>
        <td>{{ $data['prive_account_name'] }} (-)</td>
        <td style="color: #b91c1c;" data-format="#,##0.00">{{ -$data['prive'] }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;">Total Perubahan</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['additional_capital'] + $data['net_profit'] - $data['prive'] }}</td>
    </tr>
    <tr><td colspan="2"></td></tr>

    <tr>
        <td style="font-weight: bold; background-color: #f5f3ff; color: #4338ca;">Modal Akhir Periode</td>
        <td style="font-weight: bold; background-color: #f5f3ff; color: #4338ca;" data-format="#,##0.00">{{ $data['ending_capital'] }}</td>
    </tr>
@endsection
