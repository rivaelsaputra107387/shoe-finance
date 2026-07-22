<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Perubahan Ekuitas</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: Courier, monospace; }
        .header { margin-bottom: 25px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 18px; margin: 0; text-transform: uppercase; }
        .header h2 { font-size: 14px; margin: 5px 0 0 0; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 11px; }
        
        .row { display: table; width: 100%; margin: 8px 0; border-bottom: 1px dashed #eee; padding-bottom: 5px; }
        .col-left { display: table-cell; width: 65%; }
        .col-right { display: table-cell; width: 35%; text-align: right; }
        
        .grand-total-box { border: 2px solid #4f46e5; background-color: #f5f3ff; padding: 12px; border-radius: 6px; margin-top: 25px; font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header text-center" style="max-width: 600px; margin-left: auto; margin-right: auto;">
        <h1>SHOE WORKSHOP</h1>
        <h2>LAPORAN PERUBAHAN EKUITAS</h2>
        <p>Periode: {{ $data['period_name'] }}</p>
    </div>

    <div style="max-width: 600px; margin: 0 auto; padding-top: 15px;">
        <div class="row">
            <div class="col-left font-bold">{{ $data['modal_account_name'] }} (Awal Periode)</div>
            <div class="col-right font-mono font-bold">Rp {{ number_format($data['beginning_capital'], 2, ',', '.') }}</div>
        </div>

        <div class="row" style="padding-left: 15px;">
            <div class="col-left">
                {{ $data['net_profit'] >= 0 ? 'Laba Bersih Periode Berjalan (+)' : 'Rugi Bersih Periode Berjalan (-)' }}
            </div>
            <div class="col-right font-mono {{ $data['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                Rp {{ number_format($data['net_profit'], 2, ',', '.') }}
            </div>
        </div>

        @if($data['prive'] > 0)
            <div class="row" style="padding-left: 15px;">
                <div class="col-left">{{ $data['prive_account_name'] }} (-)</div>
                <div class="col-right font-mono" style="color: #b91c1c;">(Rp {{ number_format($data['prive'], 2, ',', '.') }})</div>
            </div>
        @endif

        @if($data['retained_earnings'] != 0)
            <div class="row" style="padding-left: 15px;">
                <div class="col-left">Laba Ditahan</div>
                <div class="col-right font-mono">Rp {{ number_format($data['retained_earnings'], 2, ',', '.') }}</div>
            </div>
        @endif

        <div class="row grand-total-box">
            <div style="float: left; color: #4338ca;">Modal Akhir Periode</div>
            <div class="font-mono" style="float: right; color: #4338ca;">Rp {{ number_format($data['ending_capital'], 2, ',', '.') }}</div>
            <div style="clear: both;"></div>
        </div>
    </div>
</body>
</html>
