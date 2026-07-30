@extends('reports.layout-pdf')

@section('content')
    <div style="max-width: 800px; margin: 0 auto; padding-top: 10px;">
        <table class="table">
            <tbody>
                <tr>
                    <td width="65%" class="font-bold">{{ $data['modal_account_name'] }} (Awal Periode)</td>
                    <td width="35%" class="text-right font-mono font-bold">Rp {{ number_format($data['beginning_capital'], 2, ',', '.') }}</td>
                </tr>

                <tr>
                    <td width="65%" style="padding-left: 15px;">
                        {{ $data['net_profit'] >= 0 ? 'Laba Bersih Periode Berjalan (+)' : 'Rugi Bersih Periode Berjalan (-)' }}
                    </td>
                    <td width="35%" class="text-right font-mono {{ $data['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($data['net_profit'], 2, ',', '.') }}
                    </td>
                </tr>

                @if($data['prive'] > 0)
                    <tr>
                        <td width="65%" style="padding-left: 15px;">{{ $data['prive_account_name'] }} (-)</td>
                        <td width="35%" class="text-right font-mono" style="color: #b91c1c;">(Rp {{ number_format($data['prive'], 2, ',', '.') }})</td>
                    </tr>
                @endif

                @if($data['retained_earnings'] != 0)
                    <tr>
                        <td width="65%" style="padding-left: 15px;">Laba Ditahan</td>
                        <td width="35%" class="text-right font-mono">Rp {{ number_format($data['retained_earnings'], 2, ',', '.') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <table class="table" style="margin-top: 15px;">
            <tr class="grand-total">
                <td width="65%">Modal Akhir Periode</td>
                <td width="35%" class="text-right font-mono">Rp {{ number_format($data['ending_capital'], 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>
@endsection
