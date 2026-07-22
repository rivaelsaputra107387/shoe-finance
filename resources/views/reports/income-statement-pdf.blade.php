<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Laba Rugi</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.4; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: Courier, monospace; }
        .header { margin-bottom: 25px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 18px; margin: 0; text-transform: uppercase; }
        .header h2 { font-size: 14px; margin: 5px 0 0 0; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 11px; }
        .section-title { font-size: 12px; font-weight: bold; background-color: #f3f4f6; padding: 6px 10px; margin-top: 15px; margin-bottom: 5px; text-transform: uppercase; border-bottom: 1px solid #ddd; }
        .row { display: table; width: 100%; margin: 4px 0; }
        .col-left { display: table-cell; width: 70%; padding-left: 15px; }
        .col-right { display: table-cell; width: 30%; text-align: right; }
        .total-row { border-top: 1px solid #ddd; font-weight: bold; margin-top: 8px; padding-top: 4px; }
        .summary-box { border: 2px solid #4f46e5; background-color: #f5f3ff; padding: 10px; border-radius: 6px; margin-top: 20px; font-size: 13px; font-weight: bold; }
        .profit { color: #047857; }
        .loss { color: #b91c1c; }
    </style>
</head>
<body>
    <div class="header text-center">
        <h1>SHOE WORKSHOP</h1>
        <h2>LAPORAN LABA RUGI</h2>
        <p>Periode: {{ $data['period_name'] }}</p>
    </div>

    <div>
        <!-- PENDAPATAN -->
        <div class="section-title">PENDAPATAN USAHA</div>
        @foreach($data['revenue'] as $item)
            <div class="row">
                <div class="col-left">{{ $item['name'] }}</div>
                <div class="col-right font-mono">Rp {{ number_format($item['balance'], 2, ',', '.') }}</div>
            </div>
        @endforeach
        <div class="row total-row">
            <div class="col-left" style="padding-left: 0;">Total Pendapatan</div>
            <div class="col-right font-mono">Rp {{ number_format($data['total_revenue'], 2, ',', '.') }}</div>
        </div>

        <!-- HPP -->
        <div class="section-title">HARGA POKOK PENJUALAN</div>
        @foreach($data['hpp'] as $item)
            <div class="row">
                <div class="col-left">{{ $item['name'] }}</div>
                <div class="col-right font-mono">(Rp {{ number_format($item['balance'], 2, ',', '.') }})</div>
            </div>
        @endforeach
        <div class="row total-row">
            <div class="col-left" style="padding-left: 0;">Total HPP</div>
            <div class="col-right font-mono">(Rp {{ number_format($data['total_hpp'], 2, ',', '.') }})</div>
        </div>

        <!-- LABA KOTOR -->
        <div class="row font-bold" style="background-color: #f9fafb; padding: 8px; margin-top: 10px; border: 1px solid #e5e7eb;">
            <div class="col-left" style="padding-left: 0;">LABA KOTOR</div>
            <div class="col-right font-mono">Rp {{ number_format($data['gross_profit'], 2, ',', '.') }}</div>
        </div>

        <!-- BEBAN OPERASIONAL -->
        <div class="section-title">BEBAN OPERASIONAL</div>
        @foreach($data['operational_expenses'] as $item)
            <div class="row">
                <div class="col-left">{{ $item['name'] }}</div>
                <div class="col-right font-mono">(Rp {{ number_format($item['balance'], 2, ',', '.') }})</div>
            </div>
        @endforeach
        <div class="row total-row">
            <div class="col-left" style="padding-left: 0;">Total Beban Operasional</div>
            <div class="col-right font-mono">(Rp {{ number_format($data['total_operational_expenses'], 2, ',', '.') }})</div>
        </div>

        <!-- LABA OPERASI -->
        <div class="row font-bold" style="background-color: #f9fafb; padding: 8px; margin-top: 10px; border: 1px solid #e5e7eb;">
            <div class="col-left" style="padding-left: 0;">LABA OPERASI</div>
            <div class="col-right font-mono">Rp {{ number_format($data['operating_profit'], 2, ',', '.') }}</div>
        </div>

        <!-- LAIN-LAIN -->
        @if(!empty($data['other_revenue']) || !empty($data['other_expenses']) || !empty($data['admin_expenses']))
            <div class="section-title">PENDAPATAN & BEBAN LAIN-LAIN</div>
            @foreach($data['other_revenue'] as $item)
                <div class="row">
                    <div class="col-left">{{ $item['name'] }}</div>
                    <div class="col-right font-mono text-success">Rp {{ number_format($item['balance'], 2, ',', '.') }}</div>
                </div>
            @endforeach
            @foreach($data['other_expenses'] as $item)
                <div class="row">
                    <div class="col-left">{{ $item['name'] }}</div>
                    <div class="col-right font-mono text-danger">(Rp {{ number_format($item['balance'], 2, ',', '.') }})</div>
                </div>
            @endforeach
            @foreach($data['admin_expenses'] as $item)
                <div class="row">
                    <div class="col-left">{{ $item['name'] }}</div>
                    <div class="col-right font-mono text-danger">(Rp {{ number_format($item['balance'], 2, ',', '.') }})</div>
                </div>
            @endforeach
        @endif

        <!-- LABA BERSIH -->
        <div class="row summary-box text-center">
            <span style="float: left;">LABA / RUGI BERSIH</span>
            <span class="font-mono {{ $data['net_profit'] >= 0 ? 'profit' : 'loss' }}" style="float: right;">
                Rp {{ number_format($data['net_profit'], 2, ',', '.') }}
            </span>
            <div style="clear: both;"></div>
        </div>
    </div>
</body>
</html>
