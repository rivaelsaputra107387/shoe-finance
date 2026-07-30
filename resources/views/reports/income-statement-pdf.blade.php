@extends('reports.layout-pdf')

@section('content')
    <!-- PENDAPATAN -->
    <div class="section-title">PENDAPATAN USAHA</div>
    <table class="table">
        <tbody>
            @foreach($data['revenue'] as $item)
                <tr>
                    <td width="70%">{{ $item['name'] }}</td>
                    <td width="30%" class="text-right font-mono">Rp {{ number_format($item['balance'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Total Pendapatan</td>
                <td class="text-right font-mono">Rp {{ number_format($data['total_revenue'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- HPP -->
    <div class="section-title">HARGA POKOK PENJUALAN</div>
    <table class="table">
        <tbody>
            @foreach($data['hpp'] as $item)
                <tr>
                    <td width="70%">{{ $item['name'] }}</td>
                    <td width="30%" class="text-right font-mono">(Rp {{ number_format($item['balance'], 2, ',', '.') }})</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Total HPP</td>
                <td class="text-right font-mono">(Rp {{ number_format($data['total_hpp'], 2, ',', '.') }})</td>
            </tr>
        </tbody>
    </table>

    <!-- LABA KOTOR -->
    <table class="table" style="margin-top: 15px;">
        <tr class="grand-total">
            <td width="70%">LABA KOTOR</td>
            <td width="30%" class="text-right font-mono">Rp {{ number_format($data['gross_profit'], 2, ',', '.') }}</td>
        </tr>
    </table>

    <!-- BEBAN OPERASIONAL -->
    <div class="section-title">BEBAN OPERASIONAL</div>
    <table class="table">
        <tbody>
            @foreach($data['operational_expenses'] as $item)
                <tr>
                    <td width="70%">{{ $item['name'] }}</td>
                    <td width="30%" class="text-right font-mono">(Rp {{ number_format($item['balance'], 2, ',', '.') }})</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Total Beban Operasional</td>
                <td class="text-right font-mono">(Rp {{ number_format($data['total_operational_expenses'], 2, ',', '.') }})</td>
            </tr>
        </tbody>
    </table>

    <!-- LABA OPERASI -->
    <table class="table" style="margin-top: 15px;">
        <tr class="grand-total">
            <td width="70%">LABA OPERASI</td>
            <td width="30%" class="text-right font-mono">Rp {{ number_format($data['operating_profit'], 2, ',', '.') }}</td>
        </tr>
    </table>

    <!-- LAIN-LAIN -->
    @if(!empty($data['other_revenue']) || !empty($data['other_expenses']) || !empty($data['admin_expenses']))
        <div class="section-title">PENDAPATAN & BEBAN LAIN-LAIN</div>
        <table class="table">
            <tbody>
                @foreach($data['other_revenue'] as $item)
                    <tr>
                        <td width="70%">{{ $item['name'] }}</td>
                        <td width="30%" class="text-right font-mono text-success">Rp {{ number_format($item['balance'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                @foreach($data['other_expenses'] as $item)
                    <tr>
                        <td width="70%">{{ $item['name'] }}</td>
                        <td width="30%" class="text-right font-mono text-danger">(Rp {{ number_format($item['balance'], 2, ',', '.') }})</td>
                    </tr>
                @endforeach
                @foreach($data['admin_expenses'] as $item)
                    <tr>
                        <td width="70%">{{ $item['name'] }}</td>
                        <td width="30%" class="text-right font-mono text-danger">(Rp {{ number_format($item['balance'], 2, ',', '.') }})</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- LABA BERSIH -->
    <div class="summary-box">
        <table style="width: 100%;">
            <tr>
                <td width="70%">LABA / RUGI BERSIH</td>
                <td width="30%" class="text-right font-mono {{ $data['net_profit'] >= 0 ? 'profit' : 'loss' }}">
                    Rp {{ number_format($data['net_profit'], 2, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>
@endsection
