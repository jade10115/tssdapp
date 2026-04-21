<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .page-break { page-break-after: always; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        .title { text-align: center; font-size: 18px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="title">
    <strong>ANNUAL SUPPLY REPORT - {{ $year }}</strong>
</div>

@foreach($products as $product)
<div class="product-section">

    <h3>{{ $product->product_name }} ({{ $product->unit }})</h3>

    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Starting Qty</th>
                <th>Added Qty</th>
                <th>Released Qty</th>
                <th>Remaining Qty</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
        @foreach($product->reports as $r)
            <tr>
                <td>{{ DateTime::createFromFormat('!m', $r->month)->format('F') }}</td>
                <td>{{ $r->starting_qty }}</td>
                <td>{{ $r->added_qty }}</td>
                <td>{{ $r->released_qty }}</td>
                <td>{{ $r->remaining_qty }}</td>
                <td>₱{{ number_format($r->price, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

</div>

<div class="page-break"></div>
@endforeach
<table class="table">
<thead>
  <tr>
    <th>Month</th>
    <th>Starting Qty</th>
    <th>Added</th>
    <th>Released</th>
    <th>Remaining</th>
    <th>Price</th>
  </tr>
</thead>

<tbody>
@foreach($rows as $r)
<tr>
  <td>{{ date("F", mktime(0, 0, 0, $r->month, 1)) }}</td>
  <td>{{ $r->starting_qty }}</td>
  <td>{{ $r->added_qty }}</td>
  <td>{{ $r->released_qty }}</td>
  <td>{{ $r->remaining_qty }}</td>
  <td>₱{{ number_format($r->price, 2) }}</td>
</tr>
@endforeach
</tbody>

<tfoot>
<tr style="background:#eaeaea;font-weight:bold;">
  <td>ANNUAL TOTAL</td>
  <td>{{ $summary['opening_stock'] }}</td>
  <td>{{ $summary['total_added'] }}</td>
  <td>{{ $summary['total_released'] }}</td>
  <td>{{ $summary['closing_stock'] }}</td>
  <td>₱{{ number_format($summary['avg_price'], 2) }}</td>
</tr>
</tfoot>
</table>

</body>
</html>
