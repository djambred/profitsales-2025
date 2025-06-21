<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderInvoiceController extends Controller
{
    public function show(Order $order)
    {
        return view('invoices.show', compact('order'));
    }
}
