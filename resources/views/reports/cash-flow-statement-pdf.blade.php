<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Arus Kas</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: Courier, monospace; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 16px; margin: 0; text-transform: uppercase; }
        .header h2 { font-size: 13px; margin: 4px 0 0 0; }
        .header p { margin: 4px 0 0 0; color: #666; font-size: 10px; }
        
        .section-title { font-size: 11px; font-weight: bold; background-color: #f3f4f6; padding: 5px 8px; margin-top: 15px; margin-bottom: 5px; text-transform: uppercase; border-bottom: 1px solid #ddd; }
        .row { display: table; width: 100%; margin: 4px 0; }
        .col-left { display: table-cell; width: 70%; padding-left: 15px; }
        .col-right { display: table-cell; width: 30%; text-align: right; }
        .total-row { border-top: 1px solid #ddd; font-weight: bold; margin-top: 6px; padding-top: 4px; }
        
        .summary-box { border: 2px solid #4338ca; background-color: #f5f3ff; padding: 10px; border-radius: 4px; margin-top: 15px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header text-center">
        <h1>SHOE WORKSHOP</h1>
        <h2>LAPORAN ARUS KAS</h2>
        <p>Periode: {{ $data['period_name'] }}</p>
    </div>

    @if(!$data['is_valid'])
        <div style="background-color: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 8px; margin-bottom: 12px; border-radius: 4px; text-align: center; font-weight: bold;">
            ⚠ Saldo kas akhir dari laporan ini tidak cocok dengan saldo aktual di buku besar.
        </div>
    @endif

    <div>
        <!-- OPERATING ACTIVITIES -->
        <div class="section-title" style="background-color: #eff6ff; color: #1e3a8a; border-color: #bfdbfe;">ARUS KAS DARI AKTIVITAS OPERASI</div>
        @forelse($data['operating'] as $item)
            <div class="row">
                <div class="col-left">{{ $item['account_name'] }}</div>
                <div class="col-right font-mono {{ $item['amount'] >= 0 ? '' : 'text-danger' }}">
                    {{ $item['amount'] >= 0 ? '' : '(' }}Rp {{ number_format(abs($item['amount']), 2, ',', '.') }}{{ $item['amount'] >= 0 ? '' : ')' }}
                </div>
            </div>
        @empty
            <div class="row"><div class="col-left" style="font-style: italic; color: #999;">Tidak ada transaksi</div></div>
        @endforelse
        <div class="row total-row">
            <div class="col-left" style="padding-left: 0;">Arus Kas Bersih dari Aktivitas Operasi</div>
            <div class="col-right font-mono">Rp {{ number_format($data['total_operating'], 2, ',', '.') }}</div>
        </div>

        <!-- INVESTING ACTIVITIES -->
        <div class="section-title" style="background-color: #fffbeb; color: #78350f; border-color: #fde68a;">ARUS KAS DARI AKTIVITAS INVESTASI</div>
        @forelse($data['investing'] as $item)
            <div class="row">
                <div class="col-left">{{ $item['account_name'] }}</div>
                <div class="col-right font-mono {{ $item['amount'] >= 0 ? '' : 'text-danger' }}">
                    {{ $item['amount'] >= 0 ? '' : '(' }}Rp {{ number_format(abs($item['amount']), 2, ',', '.') }}{{ $item['amount'] >= 0 ? '' : ')' }}
                </div>
            </div>
        @empty
            <div class="row"><div class="col-left" style="font-style: italic; color: #999;">Tidak ada transaksi</div></div>
        @endforelse
        <div class="row total-row">
            <div class="col-left" style="padding-left: 0;">Arus Kas Bersih dari Aktivitas Investasi</div>
            <div class="col-right font-mono">Rp {{ number_format($data['total_investing'], 2, ',', '.') }}</div>
        </div>

        <!-- FINANCING ACTIVITIES -->
        <div class="section-title" style="background-color: #faf5ff; color: #581c87; border-color: #e9d5ff;">ARUS KAS DARI AKTIVITAS PENDANAAN</div>
        @forelse($data['financing'] as $item)
            <div class="row">
                <div class="col-left">{{ $item['account_name'] }}</div>
                <div class="col-right font-mono {{ $item['amount'] >= 0 ? '' : 'text-danger' }}">
                    {{ $item['amount'] >= 0 ? '' : '(' }}Rp {{ number_format(abs($item['amount']), 2, ',', '.') }}{{ $item['amount'] >= 0 ? '' : ')' }}
                </div>
            </div>
        @empty
            <div class="row"><div class="col-left" style="font-style: italic; color: #999;">Tidak ada transaksi</div></div>
        @endforelse
        <div class="row total-row">
            <div class="col-left" style="padding-left: 0;">Arus Kas Bersih dari Aktivitas Pendanaan</div>
            <div class="col-right font-mono">Rp {{ number_format($data['total_financing'], 2, ',', '.') }}</div>
        </div>

        <!-- SUMMARY -->
        <div class="summary-box">
            <div class="row" style="border: none; padding: 0; margin: 2px 0;">
                <div style="float: left;">Kenaikan/(Penurunan) Kas Bersih</div>
                <div class="font-mono" style="float: right;">Rp {{ number_format($data['net_increase'], 2, ',', '.') }}</div>
                <div style="clear: both;"></div>
            </div>
            <div class="row" style="border: none; padding: 0; margin: 2px 0; font-weight: normal; color: #555;">
                <div style="float: left;">Saldo Kas Awal Periode</div>
                <div class="font-mono" style="float: right;">Rp {{ number_format($data['beginning_cash'], 2, ',', '.') }}</div>
                <div style="clear: both;"></div>
            </div>
            <div class="row font-bold" style="border: none; padding-top: 6px; margin-top: 6px; border-top: 1px solid #ddd; font-size: 13px;">
                <div style="float: left; color: #4338ca;">SALDO KAS AKHIR PERIODE</div>
                <div class="font-mono" style="float: right; color: #4338ca;">Rp {{ number_format($data['ending_cash'], 2, ',', '.') }}</div>
                <div style="clear: both;"></div>
            </div>
        </div>
    </div>
</body>
</html>
