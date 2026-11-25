@extends('reports.layout-pdf')

@section('content')
    @php
        // Modal awal = Modal Disetor + Laba Ditahan per awal periode. Rinciannya ditampilkan
        // sebagai komponen, lalu dijumlah, supaya setiap baris yang terlihat ikut dihitung.
        $fmt = fn ($v) => ($v < 0 ? '(' : '') . 'Rp ' . number_format(abs($v), 2, ',', '.') . ($v < 0 ? ')' : '');
        $openingPaidIn = round($data['beginning_capital'] - $data['retained_earnings'], 2);
    @endphp
    <div style="max-width: 800px; margin: 0 auto; padding-top: 10px;">
        <div class="section-title">MODAL AWAL PERIODE</div>
        <table class="table">
            <tbody>
                <tr>
                    <td width="65%" style="padding-left: 15px;">{{ $data['modal_account_name'] }}</td>
                    <td width="35%" class="text-right font-mono">{{ $fmt($openingPaidIn) }}</td>
                </tr>
                <tr>
                    <td width="65%" style="padding-left: 15px;">Laba Ditahan</td>
                    <td width="35%" class="text-right font-mono">{{ $fmt($data['retained_earnings']) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total Modal Awal Periode</td>
                    <td class="text-right font-mono">{{ $fmt($data['beginning_capital']) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">PERUBAHAN SELAMA PERIODE</div>
        <table class="table">
            <tbody>
                <tr>
                    <td width="65%" style="padding-left: 15px;">Setoran {{ $data['modal_account_name'] }} (+)</td>
                    <td width="35%" class="text-right font-mono">{{ $fmt($data['additional_capital']) }}</td>
                </tr>
                <tr>
                    <td width="65%" style="padding-left: 15px;">
                        {{ $data['net_profit'] >= 0 ? 'Laba Bersih Periode Berjalan (+)' : 'Rugi Bersih Periode Berjalan (-)' }}
                    </td>
                    <td width="35%" class="text-right font-mono {{ $data['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $fmt($data['net_profit']) }}</td>
                </tr>
                <tr>
                    <td width="65%" style="padding-left: 15px;">{{ $data['prive_account_name'] }} (-)</td>
                    <td width="35%" class="text-right font-mono" style="color: #b91c1c;">{{ $fmt(-$data['prive']) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total Perubahan</td>
                    <td class="text-right font-mono">{{ $fmt($data['additional_capital'] + $data['net_profit'] - $data['prive']) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="table" style="margin-top: 15px;">
            <tr class="grand-total">
                <td width="65%">Modal Akhir Periode</td>
                <td width="35%" class="text-right font-mono">{{ $fmt($data['ending_capital']) }}</td>
            </tr>
        </table>
    </div>
@endsection
