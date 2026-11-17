@extends('reports.layout-pdf')

@section('styles')
    <style>
        table.consolidated { width: 100%; border-collapse: collapse; font-size: 9px; }
        table.consolidated th, table.consolidated td { padding: 3px 5px; border-bottom: 1px solid #e5e7eb; }
        table.consolidated th { background: #f3f4f6; text-align: right; }
        table.consolidated th:first-child { text-align: left; }
        table.consolidated td.number { text-align: right; font-family: monospace; }
        tr.line-heading td { font-weight: bold; background: #f9fafb; padding-top: 7px; }
        tr.line-subtotal td { font-weight: bold; }
        tr.line-total td { font-weight: bold; border-top: 2px solid #111827; background: #f3f4f6; }
        .label-status { font-size: 12px; font-weight: bold; padding: 6px; border: 2px solid #111827; text-align: center; margin-bottom: 6px; }
        .status-reason { font-size: 8px; margin: 0 0 8px 12px; padding: 0; }
    </style>
@endsection

@section('content')
    <div class="label-status">{{ $report['status']['label'] }}</div>

    @if ($report['status']['reasons'] !== [])
        <ul class="status-reason">
            @foreach ($report['status']['reasons'] as $reason)
                <li>{{ $reason }}</li>
            @endforeach
        </ul>
    @endif

    @include('reports.consolidated.table', ['report' => $report])
@endsection
