@extends('reports.layout-pdf')

@section('styles')
<style>
    .container { width: 100%; display: table; table-layout: fixed; }
    .column { display: table-cell; width: 50%; vertical-align: top; box-sizing: border-box; }
    .column-left { padding-right: 15px; border-right: 1px solid #ddd; }
    .column-right { padding-left: 15px; }
    .balanced-status { font-size: 10px; color: #047857; margin-bottom: 10px; font-weight: bold; text-align: center; }
</style>
@endsection

@section('content')
    @if($data['is_balanced'])
        <div class="balanced-status">[OK] Neraca Seimbang</div>
    @else
        <div class="balanced-status" style="color: #b91c1c;">[!] PERINGATAN: Neraca Tidak Seimbang</div>
    @endif

    <div class="container">
        <!-- LEFT: ASSETS -->
        <div class="column column-left">
            <div class="section-title" style="background-color: #dbeafe; color: #1e3a8a;">ASET</div>
            
            <div style="margin-bottom: 10px;">
                <h4 style="margin: 5px 0; font-size: 11px; color: #555;">Aset Lancar</h4>
                @foreach($data['current_assets'] as $item)
                    <div class="row">
                        <div class="col-left" style="width: 65%;">{{ $item['name'] }}</div>
                        <div class="col-right font-mono" style="width: 35%;">{{ number_format($item['balance'], 2, ',', '.') }}</div>
                    </div>
                @endforeach
                <div class="row total-row">
                    <div class="col-left" style="padding-left: 0; width: 65%;">Total Aset Lancar</div>
                    <div class="col-right font-mono" style="width: 35%;">{{ number_format($data['total_current_assets'], 2, ',', '.') }}</div>
                </div>
            </div>

            <div style="margin-bottom: 10px;">
                <h4 style="margin: 5px 0; font-size: 11px; color: #555;">Aset Tetap</h4>
                @foreach($data['fixed_assets'] as $item)
                    <div class="row">
                        <div class="col-left" style="width: 65%;">{{ $item['name'] }}</div>
                        <div class="col-right font-mono" style="width: 35%;">
                            {{ $item['balance'] < 0 ? '(' . number_format(abs($item['balance']), 2, ',', '.') . ')' : number_format($item['balance'], 2, ',', '.') }}
                        </div>
                    </div>
                @endforeach
                <div class="row total-row">
                    <div class="col-left" style="padding-left: 0; width: 65%;">Total Aset Tetap</div>
                    <div class="col-right font-mono" style="width: 35%;">{{ number_format($data['total_fixed_assets'], 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="grand-total" style="background-color: #dbeafe; border-color: #3b82f6; display: table; width: 100%;">
                <div style="display: table-cell; width: 65%;">TOTAL ASET</div>
                <div class="font-mono" style="display: table-cell; width: 35%; text-align: right;">Rp {{ number_format($data['total_assets'], 2, ',', '.') }}</div>
            </div>
        </div>

        <!-- RIGHT: LIABILITIES & EQUITY -->
        <div class="column column-right">
            <div class="section-title" style="background-color: #d1fae5; color: #065f46;">KEWAJIBAN & EKUITAS</div>
            
            <div style="margin-bottom: 10px;">
                <h4 style="margin: 5px 0; font-size: 11px; color: #555;">Kewajiban Lancar</h4>
                @foreach($data['current_liabilities'] as $item)
                    <div class="row">
                        <div class="col-left" style="width: 65%;">{{ $item['name'] }}</div>
                        <div class="col-right font-mono" style="width: 35%;">{{ number_format($item['balance'], 2, ',', '.') }}</div>
                    </div>
                @endforeach
                <div class="row total-row">
                    <div class="col-left" style="padding-left: 0; width: 65%;">Total Kewajiban Lancar</div>
                    <div class="col-right font-mono" style="width: 35%;">{{ number_format($data['total_current_liabilities'], 2, ',', '.') }}</div>
                </div>
            </div>

            @if(!empty($data['long_term_liabilities']))
                <div style="margin-bottom: 10px;">
                    <h4 style="margin: 5px 0; font-size: 11px; color: #555;">Kewajiban Jangka Panjang</h4>
                    @foreach($data['long_term_liabilities'] as $item)
                        <div class="row">
                            <div class="col-left" style="width: 65%;">{{ $item['name'] }}</div>
                            <div class="col-right font-mono" style="width: 35%;">{{ number_format($item['balance'], 2, ',', '.') }}</div>
                        </div>
                    @endforeach
                    <div class="row total-row">
                        <div class="col-left" style="padding-left: 0; width: 65%;">Total Kewajiban Jangka Panjang</div>
                        <div class="col-right font-mono" style="width: 35%;">{{ number_format($data['total_long_term_liabilities'], 2, ',', '.') }}</div>
                    </div>
                </div>
            @endif

            <div style="margin-bottom: 10px;">
                <h4 style="margin: 5px 0; font-size: 11px; color: #555;">Ekuitas</h4>
                @foreach($data['equity'] as $item)
                    <div class="row">
                        <div class="col-left" style="width: 65%;">{{ $item['name'] }}</div>
                        <div class="col-right font-mono" style="width: 35%;">{{ number_format($item['balance'], 2, ',', '.') }}</div>
                    </div>
                @endforeach
                <div class="row total-row">
                    <div class="col-left" style="padding-left: 0; width: 65%;">Total Ekuitas</div>
                    <div class="col-right font-mono" style="width: 35%;">{{ number_format($data['total_equity'], 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="grand-total" style="background-color: #d1fae5; border-color: #10b981; display: table; width: 100%;">
                <div style="display: table-cell; width: 65%;">TOTAL KEWAJIBAN & EKUITAS</div>
                <div class="font-mono" style="display: table-cell; width: 35%; text-align: right;">Rp {{ number_format($data['total_liabilities_and_equity'], 2, ',', '.') }}</div>
            </div>
        </div>
    </div>
@endsection
