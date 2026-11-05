@php
    $money = fn ($value) => abs((float) $value) < 0.005
        ? '-'
        : ((float) $value < 0 ? '('.number_format(abs($value), 2, ',', '.').')' : number_format($value, 2, ',', '.'));
@endphp

<table class="consolidated">
    <thead>
        <tr>
            <th>Pos</th>
            @foreach ($report['units'] as $unit)
                <th>{{ $unit['name'] }}</th>
            @endforeach
            <th>Eliminasi</th>
            <th>Gabungan</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($report['rows'] as $row)
            @if ($row['type'] === 'heading')
                <tr class="line-heading">
                    <td colspan="{{ count($report['units']) + 3 }}">{{ $row['label'] }}</td>
                </tr>
            @else
                <tr class="line-{{ $row['type'] }}">
                    <td>{{ $row['code'] ? $row['code'].' ' : '' }}{{ $row['label'] }}</td>
                    @foreach ($report['units'] as $unit)
                        <td class="number">{{ $money($row['amounts'][$unit['code']] ?? 0) }}</td>
                    @endforeach
                    <td class="number">{{ $money($row['elimination']) }}</td>
                    <td class="number">{{ $money($row['combined']) }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
