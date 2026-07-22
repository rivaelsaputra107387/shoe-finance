<table>
    <thead>
        <tr>
            <th colspan="2">Laporan Laba Rugi</th>
        </tr>
        <tr>
            <th colspan="2">Periode: {{ $periodName }}</th>
        </tr>
        <tr>
            <th>Akun</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan="2"><strong>Pendapatan</strong></td>
        </tr>
        @foreach($data['revenues'] as $revenue)
            <tr>
                <td>{{ $revenue['code'] }} - {{ $revenue['name'] }}</td>
                <td>{{ $revenue['balance'] }}</td>
            </tr>
        @endforeach
        <tr>
            <td><strong>Total Pendapatan</strong></td>
            <td><strong>{{ $data['total_revenue'] }}</strong></td>
        </tr>
        
        <tr>
            <td colspan="2"></td>
        </tr>

        <tr>
            <td colspan="2"><strong>Beban</strong></td>
        </tr>
        @foreach($data['expenses'] as $expense)
            <tr>
                <td>{{ $expense['code'] }} - {{ $expense['name'] }}</td>
                <td>{{ $expense['balance'] }}</td>
            </tr>
        @endforeach
        <tr>
            <td><strong>Total Beban</strong></td>
            <td><strong>{{ $data['total_expense'] }}</strong></td>
        </tr>

        <tr>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td><strong>Laba Bersih</strong></td>
            <td><strong>{{ $data['net_profit'] }}</strong></td>
        </tr>
    </tbody>
</table>
