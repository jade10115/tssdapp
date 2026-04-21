<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Annual Supply Report {{ $year }}</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 3px; text-align: center; }
        .header-table td { border: 0; }
        .small { font-size: 9px; }
        .product-row { background: #f2f2f2; font-weight: bold; }
    </style>
</head>
<body>

<table class="header-table" width="100%">
    <tr>
        <td width="15%" class="small"></td>
        <td width="70%" style="text-align:center;">
            <strong>REPUBLIC OF THE PHILIPPINES</strong><br>
            <strong>DEPARTMENT OF LABOR AND EMPLOYMENT</strong><br>
            <strong>ANNUAL SUPPLY REPORT – {{ $year }}</strong>
        </td>
        <td width="15%" class="small"></td>
    </tr>
</table>

@foreach($products as $product)
    <br>
    <table>
        <tr class="product-row">
            <td colspan="9" style="text-align:left;">
                Article: {{ $product->product_name }} &nbsp;&nbsp;&nbsp;
                Unit: {{ $product->unit }}
            </td>
        </tr>
        <tr>
            <th>Month</th>
            <th>Year</th>
            <th>Unit Cost</th>
            <th>Opening Qty</th>
            <th>Qty Received</th>
            <th>Qty Issued</th>
            <th>Balance Qty</th>
            <th>Total Cost (Issued)</th>
            <th>Remarks</th>
        </tr>

        @php
            $reports = $product->reports->where('year', $year)->sortBy('month');
            $opening = optional($reports->first())->starting_qty ?? 0;
            $totalAdd = $reports->sum('added_qty');
            $totalRel = $reports->sum('released_qty');
            $closing = optional($reports->last())->remaining_qty ?? 0;
            $avgPrice = $reports->avg('price') ?? 0;
        @endphp

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
                <td colspan="9">No data for this year.</td>
            </tr>
        @endforelse

        <!-- ANNUAL SUMMARY ROW -->
        <tr>
            <td colspan="2"><strong>ANNUAL SUMMARY</strong></td>
            <td><strong>{{ number_format($avgPrice, 2) }}</strong></td>
            <td><strong>{{ $opening }}</strong></td>
            <td><strong>{{ $totalAdd }}</strong></td>
            <td><strong>{{ $totalRel }}</strong></td>
            <td><strong>{{ $closing }}</strong></td>
            <td colspan="2"></td>
        </tr>
    </table>
@endforeach

</body>
</html>
