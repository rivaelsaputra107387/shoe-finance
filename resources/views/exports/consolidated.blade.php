<table>
    <thead>
        <tr><th colspan="{{ count($report['units']) + 3 }}" style="font-weight: bold; font-size: 14px; text-align: center;">GABUNGAN: {{ strtoupper(collect($report['units'])->pluck('name')->implode(', ')) }}</th></tr>
        <tr><th colspan="{{ count($report['units']) + 3 }}" style="font-weight: bold; font-size: 12px; text-align: center;">{{ strtoupper($report['title']) }}</th></tr>
        <tr><th colspan="{{ count($report['units']) + 3 }}" style="text-align: center;">Periode: {{ $report['period']['name'] }}</th></tr>
        <tr><th colspan="{{ count($report['units']) + 3 }}" style="font-weight: bold; text-align: center;">{{ $report['status']['label'] }}</th></tr>
        @foreach ($report['status']['reasons'] as $reason)
            <tr><th colspan="{{ count($report['units']) + 3 }}" style="font-weight: normal;">{{ $reason }}</th></tr>
        @endforeach
        <tr>
            <th style="font-weight: bold;">Pos</th>
            @foreach ($report['units'] as $unit)
                <th style="font-weight: bold; text-align: right;">{{ $unit['name'] }}</th>
            @endforeach
            <th style="font-weight: bold; text-align: right;">Eliminasi</th>
            <th style="font-weight: bold; text-align: right;">Gabungan</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($report['rows'] as $row)
            @php($bold = $row['type'] !== 'row' ? 'font-weight: bold;' : '')
            @if ($row['type'] === 'heading')
                <tr><td colspan="{{ count($report['units']) + 3 }}" style="{{ $bold }}">{{ $row['label'] }}</td></tr>
            @else
                <tr>
                    <td style="{{ $bold }}">{{ $row['code'] ? $row['code'].' ' : '' }}{{ $row['label'] }}</td>
                    @foreach ($report['units'] as $unit)
                        <td style="{{ $bold }}">{{ $row['amounts'][$unit['code']] ?? 0 }}</td>
                    @endforeach
                    <td style="{{ $bold }}">{{ $row['elimination'] }}</td>
                    <td style="{{ $bold }}">{{ $row['combined'] }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
