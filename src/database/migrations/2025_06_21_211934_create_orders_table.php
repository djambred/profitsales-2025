<?php

use App\Models\CategoryOrder;
use App\Models\Client;
use App\Models\Sales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Client::class);
            $table->foreignIdFor(Sales::class);
            $table->string('order_number')->unique();
            $table->enum('category', ['SO', 'PO'])->default('SO');
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('profit', 12, 2)->default(0);
            $table->decimal('sales_profit', 12, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'converted_to_po'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
