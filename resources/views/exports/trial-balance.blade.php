@extends('exports.layout-excel', ['title' => 'NERACA SALDO', 'colspan' => 4])

@section('content')
    <tr>
        <td style="font-weight: bold; background-color: #f9fafb; text-align: center;">KODE</td>
        <td style="font-weight: bold; background-color: #f9fafb; text-align: center;">NAMA AKUN</td>
        <td style="font-weight: bold; background-color: #f9fafb; text-align: center;">DEBET</td>
        <td style="font-weight: bold; background-color: #f9fafb; text-align: center;">KREDIT</td>
    </tr>
    @foreach($data['accounts'] as $account)
        <tr>
            <td style="text-align: left;">{{ $account['code'] }}</td>
            <td>{{ $account['name'] }}</td>
            <td data-format="#,##0.00">{{ $account['debit'] > 0 ? $account['debit'] : '' }}</td>
            <td data-format="#,##0.00">{{ $account['credit'] > 0 ? $account['credit'] : '' }}</td>
        </tr>
    @endforeach
    <tr>
        <td colspan="2" style="font-weight: bold; text-align: right;">TOTAL</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_debit'] }}</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $data['total_credit'] }}</td>
    </tr>
    <tr><td colspan="4"></td></tr>
    
    @if($data['is_balanced'])
        <tr>
            <td colspan="4" style="background-color: #ecfdf5; color: #047857; text-align: center; font-weight: bold;">
                ✓ NERACA SALDO SEIMBANG (BALANCE)
            </td>
        </tr>
    @else
        <tr>
            <td colspan="4" style="background-color: #fef2f2; color: #b91c1c; text-align: center; font-weight: bold;">
                ⚠ PERINGATAN: NERACA SALDO TIDAK SEIMBANG
            </td>
        </tr>
    @endif
@endsection
