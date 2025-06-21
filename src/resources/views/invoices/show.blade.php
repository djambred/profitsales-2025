<!DOCTYPE html>
<html>

<head>
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>

<body>
    <h1>Invoice: {{ $order->order_number }}</h1>
    <p><strong>Client:</strong> {{ $order->client->user->name }}</p>
    <p><strong>Sales:</strong> {{ $order->sale->employee->user->name ?? '-' }}</p>
    <p><strong>Status:</strong> {{ $order->status }}</p>
    <p><strong>Date:</strong> {{ $order->created_at->format('d M Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->orderDetails as $detail)
                <tr>
                    <td>{{ $detail->product->name }}</td>
                    <td>{{ $detail->quantity }}</td>
                    <td>{{ number_format($detail->price, 2) }}</td>
                    <td>{{ number_format($detail->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Total: Rp {{ number_format($order->total, 2) }}</h2>
</body>

</html>
