<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesCommissions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $client = Client::first();
        $sales = Sales::first();
        $product = Product::first();

        $order = Order::create([
            'client_id' => $client->id,
            'sales_id' => $sales->id,
            'order_number' => 'ORD-0001',
            'category' => 'PO',
            'status' => 'converted_to_po',
            'total' => 50000,
            'profit' => 0,
            'sales_profit' => 0,
            'notes' => 'Initial sales order.',
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'price' => 10000,
            'subtotal' => 50000,
        ]);

        $profit = $order->calculateProfit();
        $salesProfit = $order->calculateSalesProfit();

        $order->update([
            'profit' => $profit,
            'sales_profit' => $salesProfit,
        ]);

        SalesCommissions::create([
            'sales_id' => $sales->id,
            'order_id' => $order->id,
            'amount' => $salesProfit,
        ]);
    }
}
