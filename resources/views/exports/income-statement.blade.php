@extends('exports.layout-excel', ['title' => 'LAPORAN LABA RUGI', 'colspan' => 2])

@section('content')
    <!-- PENDAPATAN -->
    <tr>
        <td colspan="2" style="font-weight: bold; background-color: #f3f4f6;">PENDAPATAN USAHA</td>
    </tr>
    @foreach($data['revenue'] as $item)
        <tr>
            <td>{{ $item['name'] }}</td>
            <td data-format="#,##0.00">{{ $item['balance'] }}</td>
        </tr>
    @endforeach
    <tr>
        <td style="font-weight: bold;">Total Pendapatan</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_revenue'] }}</td>
    </tr>
    <tr><td colspan="2"></td></tr>

    <!-- HPP -->
    <tr>
        <td colspan="2" style="font-weight: bold; background-color: #f3f4f6;">HARGA POKOK PENJUALAN</td>
    </tr>
    @foreach($data['hpp'] as $item)
        <tr>
            <td>{{ $item['name'] }}</td>
            <td data-format="#,##0.00">{{ -$item['balance'] }}</td>
        </tr>
    @endforeach
    <tr>
        <td style="font-weight: bold;">Total HPP</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ -$data['total_hpp'] }}</td>
    </tr>
    
    <tr>
        <td style="font-weight: bold; background-color: #e5e7eb;">LABA KOTOR</td>
        <td style="font-weight: bold; background-color: #e5e7eb;" data-format="#,##0.00">{{ $data['gross_profit'] }}</td>
    </tr>
    <tr><td colspan="2"></td></tr>

    <!-- BEBAN OPERASIONAL -->
    <tr>
        <td colspan="2" style="font-weight: bold; background-color: #f3f4f6;">BEBAN OPERASIONAL</td>
    </tr>
    @foreach($data['operational_expenses'] as $item)
        <tr>
            <td>{{ $item['name'] }}</td>
            <td data-format="#,##0.00">{{ -$item['balance'] }}</td>
        </tr>
    @endforeach
    <tr>
        <td style="font-weight: bold;">Total Beban Operasional</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ -$data['total_operational_expenses'] }}</td>
    </tr>
    
    <tr>
        <td style="font-weight: bold; background-color: #e5e7eb;">LABA OPERASI</td>
        <td style="font-weight: bold; background-color: #e5e7eb;" data-format="#,##0.00">{{ $data['operating_profit'] }}</td>
    </tr>
    <tr><td colspan="2"></td></tr>

    <!-- LAIN-LAIN -->
    @if(!empty($data['other_revenue']) || !empty($data['other_expenses']) || !empty($data['admin_expenses']))
        <tr>
            <td colspan="2" style="font-weight: bold; background-color: #f3f4f6;">PENDAPATAN & BEBAN LAIN-LAIN</td>
        </tr>
        @foreach($data['other_revenue'] as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td style="color: #047857;" data-format="#,##0.00">{{ $item['balance'] }}</td>
            </tr>
        @endforeach
        @foreach($data['other_expenses'] as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td style="color: #b91c1c;" data-format="#,##0.00">{{ -$item['balance'] }}</td>
            </tr>
        @endforeach
        @foreach($data['admin_expenses'] as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td style="color: #b91c1c;" data-format="#,##0.00">{{ -$item['balance'] }}</td>
            </tr>
        @endforeach
        <tr><td colspan="2"></td></tr>
    @endif

    <!-- LABA BERSIH -->
    <tr>
        <td style="font-weight: bold; background-color: #dbeafe; font-size: 12px;">LABA / RUGI BERSIH</td>
        <td style="font-weight: bold; background-color: #dbeafe; font-size: 12px; color: {{ $data['net_profit'] >= 0 ? '#047857' : '#b91c1c' }}" data-format="#,##0.00">
            {{ $data['net_profit'] }}
        </td>
    </tr>
@endsection
