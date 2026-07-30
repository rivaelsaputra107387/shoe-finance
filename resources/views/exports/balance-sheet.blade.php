@extends('exports.layout-excel', ['title' => 'NERACA', 'colspan' => 4])

@section('content')
    <!-- BALANCE SHEET HAS 4 COLUMNS: ASSET (A, B) and LIABILITIES (C, D) -->
    <tr>
        <td colspan="4" style="text-align: center; font-weight: bold; color: {{ $data['is_balanced'] ? '#047857' : '#b91c1c' }}">
            {{ $data['is_balanced'] ? '✓ Neraca Seimbang' : '⚠ PERINGATAN: Neraca Tidak Seimbang' }}
        </td>
    </tr>
    <tr><td colspan="4"></td></tr>

    <tr>
        <td colspan="2" style="font-weight: bold; background-color: #dbeafe; text-align: center;">ASET</td>
        <td colspan="2" style="font-weight: bold; background-color: #d1fae5; text-align: center;">KEWAJIBAN & EKUITAS</td>
    </tr>
    <tr>
        <td colspan="2" style="font-weight: bold;">Aset Lancar</td>
        <td colspan="2" style="font-weight: bold;">Kewajiban Lancar</td>
    </tr>

    @php
        $maxRows = max(count($data['current_assets']), count($data['current_liabilities']));
    @endphp
    @for($i = 0; $i < $maxRows; $i++)
        <tr>
            <!-- Left Side (Aset Lancar) -->
            @if(isset($data['current_assets'][$i]))
                <td>{{ $data['current_assets'][$i]['name'] }}</td>
                <td data-format="#,##0.00">{{ $data['current_assets'][$i]['balance'] }}</td>
            @else
                <td></td><td></td>
            @endif

            <!-- Right Side (Kewajiban Lancar) -->
            @if(isset($data['current_liabilities'][$i]))
                <td>{{ $data['current_liabilities'][$i]['name'] }}</td>
                <td data-format="#,##0.00">{{ $data['current_liabilities'][$i]['balance'] }}</td>
            @else
                <td></td><td></td>
            @endif
        </tr>
    @endfor

    <!-- Totals for Current Assets & Current Liabilities -->
    <tr>
        <td style="font-weight: bold;">Total Aset Lancar</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_current_assets'] }}</td>
        
        <td style="font-weight: bold;">Total Kewajiban Lancar</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_current_liabilities'] }}</td>
    </tr>
    <tr><td colspan="4"></td></tr>

    <!-- FIXED ASSETS & LONG TERM LIABILITIES / EQUITY -->
    <tr>
        <td colspan="2" style="font-weight: bold;">Aset Tetap</td>
        @if(!empty($data['long_term_liabilities']))
            <td colspan="2" style="font-weight: bold;">Kewajiban Jangka Panjang</td>
        @else
            <td colspan="2" style="font-weight: bold;">Ekuitas</td>
        @endif
    </tr>

    @php
        $rightList = !empty($data['long_term_liabilities']) ? $data['long_term_liabilities'] : $data['equity'];
        $maxRows2 = max(count($data['fixed_assets']), count($rightList));
    @endphp
    @for($i = 0; $i < $maxRows2; $i++)
        <tr>
            <!-- Left Side (Aset Tetap) -->
            @if(isset($data['fixed_assets'][$i]))
                <td>{{ $data['fixed_assets'][$i]['name'] }}</td>
                <td data-format="#,##0.00">{{ $data['fixed_assets'][$i]['balance'] }}</td>
            @else
                <td></td><td></td>
            @endif

            <!-- Right Side (Kewajiban Jangka Panjang or Equity) -->
            @if(isset($rightList[$i]))
                <td>{{ $rightList[$i]['name'] }}</td>
                <td data-format="#,##0.00">{{ $rightList[$i]['balance'] }}</td>
            @else
                <td></td><td></td>
            @endif
        </tr>
    @endfor

    <!-- Totals -->
    <tr>
        <td style="font-weight: bold;">Total Aset Tetap</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_fixed_assets'] }}</td>
        
        @if(!empty($data['long_term_liabilities']))
            <td style="font-weight: bold;">Total Kewajiban Jk. Panjang</td>
            <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_long_term_liabilities'] }}</td>
        @else
            <td style="font-weight: bold;">Total Ekuitas</td>
            <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_equity'] }}</td>
        @endif
    </tr>
    <tr><td colspan="4"></td></tr>

    <!-- If long term liabilities existed, we still need to show equity below -->
    @if(!empty($data['long_term_liabilities']))
        <tr>
            <td colspan="2"></td>
            <td colspan="2" style="font-weight: bold;">Ekuitas</td>
        </tr>
        @foreach($data['equity'] as $item)
            <tr>
                <td></td><td></td>
                <td>{{ $item['name'] }}</td>
                <td data-format="#,##0.00">{{ $item['balance'] }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="2"></td>
            <td style="font-weight: bold;">Total Ekuitas</td>
            <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_equity'] }}</td>
        </tr>
        <tr><td colspan="4"></td></tr>
    @endif

    <!-- GRAND TOTALS -->
    <tr>
        <td style="font-weight: bold; background-color: #dbeafe; font-size: 12px;">TOTAL ASET</td>
        <td style="font-weight: bold; background-color: #dbeafe; font-size: 12px;" data-format="#,##0.00">{{ $data['total_assets'] }}</td>
        <td style="font-weight: bold; background-color: #d1fae5; font-size: 12px;">TOTAL KEWAJIBAN & EKUITAS</td>
        <td style="font-weight: bold; background-color: #d1fae5; font-size: 12px;" data-format="#,##0.00">{{ $data['total_liabilities_and_equity'] }}</td>
    </tr>
@endsection
