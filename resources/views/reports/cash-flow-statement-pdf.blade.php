@extends('reports.layout-pdf')

@section('content')
    @if(!$data['is_valid'])
        <div style="background-color: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 8px; margin-bottom: 12px; border-radius: 4px; text-align: center; font-weight: bold;">
            [!] Saldo kas akhir dari laporan ini tidak cocok dengan saldo aktual di buku besar.
        </div>
    @endif

    <div>
        <!-- OPERATING ACTIVITIES -->
        <div class="section-title" style="background-color: #eff6ff; color: #1e3a8a; border-color: #bfdbfe;">ARUS KAS DARI AKTIVITAS OPERASI</div>
        <table class="table">
            <tbody>
                @forelse($data['operating'] as $item)
                    <tr>
                        <td width="70%">{{ $item['account_name'] }}</td>
                        <td width="30%" class="text-right font-mono {{ $item['amount'] >= 0 ? '' : 'text-danger' }}">
                            {{ $item['amount'] >= 0 ? '' : '(' }}Rp {{ number_format(abs($item['amount']), 2, ',', '.') }}{{ $item['amount'] >= 0 ? '' : ')' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" style="font-style: italic; color: #999;">Tidak ada transaksi</td></tr>
                @endforelse
                <tr class="total-row">
                    <td>Arus Kas Bersih dari Aktivitas Operasi</td>
                    <td class="text-right font-mono">Rp {{ number_format($data['total_operating'], 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- INVESTING ACTIVITIES -->
        <div class="section-title" style="background-color: #fffbeb; color: #78350f; border-color: #fde68a;">ARUS KAS DARI AKTIVITAS INVESTASI</div>
        <table class="table">
            <tbody>
                @forelse($data['investing'] as $item)
                    <tr>
                        <td width="70%">{{ $item['account_name'] }}</td>
                        <td width="30%" class="text-right font-mono {{ $item['amount'] >= 0 ? '' : 'text-danger' }}">
                            {{ $item['amount'] >= 0 ? '' : '(' }}Rp {{ number_format(abs($item['amount']), 2, ',', '.') }}{{ $item['amount'] >= 0 ? '' : ')' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" style="font-style: italic; color: #999;">Tidak ada transaksi</td></tr>
                @endforelse
                <tr class="total-row">
                    <td>Arus Kas Bersih dari Aktivitas Investasi</td>
                    <td class="text-right font-mono">Rp {{ number_format($data['total_investing'], 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- FINANCING ACTIVITIES -->
        <div class="section-title" style="background-color: #faf5ff; color: #581c87; border-color: #e9d5ff;">ARUS KAS DARI AKTIVITAS PENDANAAN</div>
        <table class="table">
            <tbody>
                @forelse($data['financing'] as $item)
                    <tr>
                        <td width="70%">{{ $item['account_name'] }}</td>
                        <td width="30%" class="text-right font-mono {{ $item['amount'] >= 0 ? '' : 'text-danger' }}">
                            {{ $item['amount'] >= 0 ? '' : '(' }}Rp {{ number_format(abs($item['amount']), 2, ',', '.') }}{{ $item['amount'] >= 0 ? '' : ')' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" style="font-style: italic; color: #999;">Tidak ada transaksi</td></tr>
                @endforelse
                <tr class="total-row">
                    <td>Arus Kas Bersih dari Aktivitas Pendanaan</td>
                    <td class="text-right font-mono">Rp {{ number_format($data['total_financing'], 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- SUMMARY -->
        <div class="summary-box">
            <table style="width: 100%;">
                <tr>
                    <td width="70%" style="font-weight: normal;">Kenaikan/(Penurunan) Kas Bersih</td>
                    <td width="30%" class="text-right font-mono font-bold">Rp {{ number_format($data['net_increase'], 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td width="70%" style="font-weight: normal; color: #555;">Saldo Kas Awal Periode</td>
                    <td width="30%" class="text-right font-mono font-bold">Rp {{ number_format($data['beginning_cash'], 2, ',', '.') }}</td>
                </tr>
                <tr><td colspan="2"><hr style="border: 0; border-top: 1px solid #ccc; margin: 5px 0;"></td></tr>
                <tr>
                    <td width="70%" style="color: #4338ca; font-weight: bold; font-size: 13px;">SALDO KAS AKHIR PERIODE</td>
                    <td width="30%" class="text-right font-mono font-bold" style="color: #4338ca; font-size: 13px;">Rp {{ number_format($data['ending_cash'], 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>
@endsection
