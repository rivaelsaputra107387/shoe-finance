<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Laporan Keuangan' }}</title>
    <style>
        @page {
            margin: 40px 40px;
        }
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            font-size: 11px; 
            color: #333; 
            line-height: 1.4; 
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        
        .header { 
            margin-bottom: 25px; 
            border-bottom: 2px solid #333; 
            padding-bottom: 15px;
            display: table;
            width: 100%;
        }
        .header-logo {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
        }
        .header-logo img {
            max-width: 70px;
            height: auto;
        }
        .header-content {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .header-empty {
            display: table-cell;
            width: 80px; /* Balance the logo on the left for true centering */
        }
        
        .header h1 { font-size: 18px; margin: 0; text-transform: uppercase; letter-spacing: 1px; color: #111; }
        .header h2 { font-size: 14px; margin: 5px 0 0 0; color: #444; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 11px; }
        
        .section-title { 
            font-size: 11px; 
            font-weight: bold; 
            background-color: #f3f4f6; 
            padding: 6px 10px; 
            margin-top: 15px; 
            margin-bottom: 5px; 
            text-transform: uppercase; 
            border-bottom: 1px solid #ddd; 
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th, .table td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
        }
        .table th {
            background-color: #f9fafb;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            color: #555;
            border-bottom: 1px solid #ddd;
            border-top: 1px solid #ddd;
        }
        
        .row { display: table; width: 100%; margin: 4px 0; }
        .col-left { display: table-cell; width: 70%; padding-left: 15px; }
        .col-right { display: table-cell; width: 30%; text-align: right; }
        
        .total-row { 
            border-top: 1px solid #ccc; 
            font-weight: bold; 
            margin-top: 6px; 
            padding-top: 6px; 
            padding-bottom: 6px;
        }
        .grand-total {
            border-top: 2px solid #333;
            border-bottom: 2px double #333;
            background-color: #f9fafb;
            font-weight: bold;
            margin-top: 10px;
            padding: 8px;
        }
        
        .summary-box { 
            border: 2px solid #4f46e5; 
            background-color: #f5f3ff; 
            padding: 10px; 
            border-radius: 4px; 
            margin-top: 20px; 
            font-size: 12px; 
            font-weight: bold; 
        }
        .profit { color: #047857; }
        .loss { color: #b91c1c; }
        .text-success { color: #047857; }
        .text-danger { color: #b91c1c; }

        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 30px;
            text-align: right;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
        .page-number:after { content: counter(page); }
    </style>
    @yield('styles')
</head>
<body>
    @php
        $logoPath = public_path('logo.png');
        $logoBase64 = '';
        if(file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
    @endphp

    <div class="header">
        <div class="header-logo">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo">
            @endif
        </div>
        <div class="header-content">
            <h1>SHOE WORKSHOP</h1>
            <h2>{{ $title ?? 'LAPORAN KEUANGAN' }}</h2>
            <p>{{ $subtitle ?? 'Periode: ' . ($data['period_name'] ?? 'Semua Periode') }}</p>
        </div>
        <div class="header-empty"></div>
    </div>

    <div class="content">
        @yield('content')
    </div>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }} | Halaman <span class="page-number"></span>
    </div>
</body>
</html>
