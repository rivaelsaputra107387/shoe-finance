<table>
    <thead>
        <tr>
            <th colspan="{{ $colspan ?? 5 }}" style="font-weight: bold; font-size: 14px; text-align: center;">SHOE WORKSHOP</th>
        </tr>
        <tr>
            <th colspan="{{ $colspan ?? 5 }}" style="font-weight: bold; font-size: 12px; text-align: center;">{{ $title ?? 'LAPORAN KEUANGAN' }}</th>
        </tr>
        <tr>
            <th colspan="{{ $colspan ?? 5 }}" style="text-align: center; color: #666666;">
                {{ $subtitle ?? 'Periode: ' . ($data['period_name'] ?? 'Semua Periode') }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ $colspan ?? 5 }}"></th> <!-- Empty row for spacing -->
        </tr>
    </thead>
    <tbody>
        @yield('content')
    </tbody>
</table>
