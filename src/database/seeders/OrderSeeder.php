<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesCommissions;
use Illuminate\Database\Seeder;
use App\Enums\OrderStatus;
use App\Models\OrderFlow;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $client = Client::first();
        $sales = Sales::whereNotNull('user_id')->first(); // pastikan ada user_id
        $product = Product::first();

        if (!$client || !$sales || !$product) {
            $this->command->error('Missing required related data: client, sales, or product.');
            return;
        }

        // Create order
        $order = Order::create([
            'client_id'     => $client->id,
            'sales_id'      => $sales->id,
            'order_number'  => 'ORD-0001',
            'category'      => 'PO',
            'status'        => OrderStatus::ConvertedToPO,
            'total'         => 50000,
            'profit'        => 0,
            'sales_profit'  => 0,
            'notes'         => 'Initial sales order.',
        ]);

        // Add one order detail
        OrderDetail::create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => 5,
            'price'      => 10000,
            'subtotal'   => 50000,
        ]);

        // Calculate profits
        $profit = $order->calculateProfit();
        $salesProfit = $order->calculateSalesProfit();

        $order->update([
            'profit'       => $profit,
            'sales_profit' => $salesProfit,
        ]);

        // Commission
        SalesCommissions::create([
            'sales_id' => $sales->id,
            'order_id' => $order->id,
            'amount'   => $salesProfit,
        ]);


        OrderFlow::create([
            'order_id'    => $order->id,
            'user_id'    => 2, // ✅ fixed: use sales_id not user_id
            'from_status' => null,
            'to_status'   => OrderStatus::ConvertedToPO,
            'notes'       => 'Auto-converted to PO during seeding.',
        ]);
    }
}
