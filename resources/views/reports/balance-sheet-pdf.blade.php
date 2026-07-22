<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Neraca</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.3; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: Courier, monospace; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 16px; margin: 0; text-transform: uppercase; }
        .header h2 { font-size: 13px; margin: 4px 0 0 0; }
        .header p { margin: 4px 0 0 0; color: #666; font-size: 10px; }
        
        .container { width: 100%; display: table; table-layout: fixed; }
        .column { display: table-cell; width: 50%; vertical-align: top; box-sizing: border-box; }
        .column-left { padding-right: 15px; border-right: 1px solid #ddd; }
        .column-right { padding-left: 15px; }

        .section-title { font-size: 11px; font-weight: bold; background-color: #f3f4f6; padding: 5px 8px; margin-top: 10px; margin-bottom: 5px; text-transform: uppercase; border-bottom: 1px solid #ddd; }
        .row { display: table; width: 100%; margin: 3px 0; }
        .col-left { display: table-cell; width: 65%; padding-left: 10px; }
        .col-right { display: table-cell; width: 35%; text-align: right; }
        .total-row { border-top: 1px solid #ddd; font-weight: bold; margin-top: 6px; padding-top: 3px; }
        .grand-total-box { border: 2px solid #3b82f6; background-color: #eff6ff; padding: 8px; border-radius: 4px; margin-top: 15px; font-size: 12px; font-weight: bold; }
        .balanced-status { font-size: 10px; color: #047857; margin-bottom: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header text-center">
        <h1>SHOE WORKSHOP</h1>
        <h2>NERACA</h2>
        <p>Per {{ $data['period_name'] }}</p>
    </div>

    @if($data['is_balanced'])
        <div class="balanced-status text-center">✓ Neraca Seimbang</div>
    @else
        <div class="balanced-status text-center" style="color: #b91c1c;">⚠ PERINGATAN: Neraca Tidak Seimbang</div>
    @endif

    <div class="container">
        <!-- LEFT: ASSETS -->
        <div class="column column-left">
            <div class="section-title" style="background-color: #dbeafe; color: #1e3a8a;">ASET</div>
            
            <div style="margin-bottom: 10px;">
                <h4 style="margin: 5px 0; font-size: 11px; color: #555;">Aset Lancar</h4>
                @foreach($data['current_assets'] as $item)
                    <div class="row">
                        <div class="col-left">{{ $item['name'] }}</div>
                        <div class="col-right font-mono">{{ number_format($item['balance'], 2, ',', '.') }}</div>
                    </div>
                @endforeach
                <div class="row total-row">
                    <div class="col-left" style="padding-left: 0;">Total Aset Lancar</div>
                    <div class="col-right font-mono">{{ number_format($data['total_current_assets'], 2, ',', '.') }}</div>
                </div>
            </div>

            <div style="margin-bottom: 10px;">
                <h4 style="margin: 5px 0; font-size: 11px; color: #555;">Aset Tetap</h4>
                @foreach($data['fixed_assets'] as $item)
                    <div class="row">
                        <div class="col-left">{{ $item['name'] }}</div>
                        <div class="col-right font-mono">
                            {{ $item['balance'] < 0 ? '(' . number_format(abs($item['balance']), 2, ',', '.') . ')' : number_format($item['balance'], 2, ',', '.') }}
                        </div>
                    </div>
                @endforeach
                <div class="row total-row">
                    <div class="col-left" style="padding-left: 0;">Total Aset Tetap</div>
                    <div class="col-right font-mono">{{ number_format($data['total_fixed_assets'], 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="row grand-total-box" style="background-color: #dbeafe; border-color: #3b82f6;">
                <div style="float: left;">TOTAL ASET</div>
                <div class="font-mono" style="float: right;">Rp {{ number_format($data['total_assets'], 2, ',', '.') }}</div>
                <div style="clear: both;"></div>
            </div>
        </div>

        <!-- RIGHT: LIABILITIES & EQUITY -->
        <div class="column column-right">
            <div class="section-title" style="background-color: #d1fae5; color: #065f46;">KEWAJIBAN & EKUITAS</div>
            
            <div style="margin-bottom: 10px;">
                <h4 style="margin: 5px 0; font-size: 11px; color: #555;">Kewajiban Lancar</h4>
                @foreach($data['current_liabilities'] as $item)
                    <div class="row">
                        <div class="col-left">{{ $item['name'] }}</div>
                        <div class="col-right font-mono">{{ number_format($item['balance'], 2, ',', '.') }}</div>
                    </div>
                @endforeach
                <div class="row total-row">
                    <div class="col-left" style="padding-left: 0;">Total Kewajiban Lancar</div>
                    <div class="col-right font-mono">{{ number_format($data['total_current_liabilities'], 2, ',', '.') }}</div>
                </div>
            </div>

            @if(!empty($data['long_term_liabilities']))
                <div style="margin-bottom: 10px;">
                    <h4 style="margin: 5px 0; font-size: 11px; color: #555;">Kewajiban Jangka Panjang</h4>
                    @foreach($data['long_term_liabilities'] as $item)
                        <div class="row">
                            <div class="col-left">{{ $item['name'] }}</div>
                            <div class="col-right font-mono">{{ number_format($item['balance'], 2, ',', '.') }}</div>
                        </div>
                    @endforeach
                    <div class="row total-row">
                        <div class="col-left" style="padding-left: 0;">Total Kewajiban Jangka Panjang</div>
                        <div class="col-right font-mono">{{ number_format($data['total_long_term_liabilities'], 2, ',', '.') }}</div>
                    </div>
                </div>
            @endif

            <div style="margin-bottom: 10px;">
                <h4 style="margin: 5px 0; font-size: 11px; color: #555;">Ekuitas</h4>
                @foreach($data['equity'] as $item)
                    <div class="row">
                        <div class="col-left">{{ $item['name'] }}</div>
                        <div class="col-right font-mono">{{ number_format($item['balance'], 2, ',', '.') }}</div>
                    </div>
                @endforeach
                <div class="row total-row">
                    <div class="col-left" style="padding-left: 0;">Total Ekuitas</div>
                    <div class="col-right font-mono">{{ number_format($data['total_equity'], 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="row grand-total-box" style="background-color: #d1fae5; border-color: #10b981;">
                <div style="float: left;">TOTAL KEWAJIBAN & EKUITAS</div>
                <div class="font-mono" style="float: right;">Rp {{ number_format($data['total_liabilities_and_equity'], 2, ',', '.') }}</div>
                <div style="clear: both;"></div>
            </div>
        </div>
    </div>
</body>
</html>
