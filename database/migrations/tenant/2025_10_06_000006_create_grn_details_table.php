<?php

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
        Schema::create('grn_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('grn_summary_id')->constrained('grn_summaries')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->decimal('quantity', 15, 2);
            $table->decimal('cost', 15, 2);
            $table->decimal('total', 15, 2);
            $table->foreignId('purchase_order_detail_id')->nullable()->constrained('purchase_order_details')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grn_details');
    }
};
