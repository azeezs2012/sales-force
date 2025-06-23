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
        Schema::create('grn_summaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('grn_date');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->text('grn_billing_address')->nullable();
            $table->text('grn_delivery_address')->nullable();
            $table->foreignId('ap_account_id')->constrained('accounts')->onDelete('cascade');
            $table->string('grn_status')->default('draft'); // e.g., draft, posted
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grn_summaries');
    }
};
