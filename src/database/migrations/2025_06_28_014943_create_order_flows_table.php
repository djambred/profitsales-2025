<?php

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
        Schema::create('order_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained(); // the actor who made the change
            $table->string('from_status')->nullable(); // nullable for initial creation
            $table->string('to_status'); // e.g. 'pending' → 'approved'
            $table->text('notes')->nullable(); // optional remarks (reason, context)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_flows');
    }
};
