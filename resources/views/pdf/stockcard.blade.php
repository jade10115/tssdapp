<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Stock Card - {{ $product->product_name }}</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; }
        .header-table td { border: 0; }
        .title { text-align: center; font-weight: bold; margin-bottom: 5px; }
        .small { font-size: 10px; }
    </style>
</head>
<body>

    <table class="header-table" width="100%">
        <tr>
            <td width="15%" class="small">
                <!-- put DOE/DOLE logo here if you want -->
            </td>
            <td width="70%" style="text-align:center;">
                <strong>REPUBLIC OF THE PHILIPPINES</strong><br>
                <strong>DEPARTMENT OF LABOR AND EMPLOYMENT</strong><br>
                <strong>SUPPLY AND MATERIALS STOCK CARD</strong>
            </td>
            <td width="15%" class="small">
                <!-- office code, fund, etc -->
            </td>
        </tr>
    </table>

    <p class="small">
        Article: <strong>{{ $product->product_name }}</strong><br>
        Unit: <strong>{{ $product->unit }}</strong><br>
    </p>

    <table>
        <thead>
        <tr>
            <th width="8%">Month</th>
            <th width="8%">Year</th>
            <th width="14%">Unit Cost</th>
            <th width="10%">Opening Qty</th>
            <th width="10%">Qty Received</th>
            <th width="10%">Qty Issued</th>
            <th width="10%">Balance Qty</th>
            <th width="10%">Total Cost (Issued)</th>
            <th width="20%">Remarks</th>
        </tr>
        </thead>
        <tbody>
        @forelse($reports as $r)
            <tr>
                <td>{{ \Carbon\Carbon::create()->month($r->month)->format('M') }}</td>
                <td>{{ $r->year }}</td>
                <td>{{ number_format($r->price, 2) }}</td>
                <td>{{ $r->starting_qty }}</td>
                <td>{{ $r->added_qty }}</td>
                <td>{{ $r->released_qty }}</td>
                <td>{{ $r->remaining_qty }}</td>
                <td>{{ number_format($r->price * $r->released_qty, 2) }}</td>
                <td></td>
            </tr>
        @empty
            <tr>
                <td colspan="9">No report data.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

</body>
</html>
