@extends('exports.layout-excel', ['title' => 'LAPORAN PERUBAHAN EKUITAS', 'colspan' => 2])

@section('content')
    <tr>
        <td style="font-weight: bold;">{{ $data['modal_account_name'] }} (Awal Periode)</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['beginning_capital'] }}</td>
    </tr>

    <tr>
        <td>{{ $data['net_profit'] >= 0 ? 'Laba Bersih Periode Berjalan (+)' : 'Rugi Bersih Periode Berjalan (-)' }}</td>
        <td style="color: {{ $data['net_profit'] >= 0 ? '#047857' : '#b91c1c' }};" data-format="#,##0.00">{{ $data['net_profit'] }}</td>
    </tr>

    @if($data['prive'] > 0)
        <tr>
            <td>{{ $data['prive_account_name'] }} (-)</td>
            <td style="color: #b91c1c;" data-format="#,##0.00">{{ -$data['prive'] }}</td>
        </tr>
    @endif

    @if($data['retained_earnings'] != 0)
        <tr>
            <td>Laba Ditahan</td>
            <td data-format="#,##0.00">{{ $data['retained_earnings'] }}</td>
        </tr>
    @endif
    <tr><td colspan="2"></td></tr>

    <tr>
        <td style="font-weight: bold; background-color: #f5f3ff; color: #4338ca;">Modal Akhir Periode</td>
        <td style="font-weight: bold; background-color: #f5f3ff; color: #4338ca;" data-format="#,##0.00">{{ $data['ending_capital'] }}</td>
    </tr>
@endsection
