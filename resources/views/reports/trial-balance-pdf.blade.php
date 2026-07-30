@extends('reports.layout-pdf')

@section('content')
    <div style="margin-top: 15px;">
        <table class="table">
            <thead>
                <tr>
                    <th class="text-left" style="width: 15%;">KODE</th>
                    <th class="text-left" style="width: 45%;">NAMA AKUN</th>
                    <th class="text-right" style="width: 20%;">DEBET</th>
                    <th class="text-right" style="width: 20%;">KREDIT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['accounts'] as $account)
                    <tr>
                        <td class="font-mono">{{ $account['code'] }}</td>
                        <td>{{ $account['name'] }}</td>
                        <td class="text-right font-mono">{{ $account['debit'] > 0 ? 'Rp ' . number_format($account['debit'], 2, ',', '.') : '-' }}</td>
                        <td class="text-right font-mono">{{ $account['credit'] > 0 ? 'Rp ' . number_format($account['credit'], 2, ',', '.') : '-' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="text-right">TOTAL</td>
                    <td class="text-right font-mono">Rp {{ number_format($data['total_debit'], 2, ',', '.') }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($data['total_credit'], 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($data['is_balanced'])
        <div class="summary-box text-center" style="color: #047857; border-color: #047857; background-color: #ecfdf5;">
            [OK] NERACA SALDO SEIMBANG (BALANCE)
        </div>
    @else
        <div class="summary-box text-center" style="color: #b91c1c; border-color: #b91c1c; background-color: #fef2f2;">
            [!] PERINGATAN: NERACA SALDO TIDAK SEIMBANG
        </div>
    @endif
@endsection
