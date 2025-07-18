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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->date('payment_date');
            $table->decimal('payment_amount', 15, 2);
            $table->string('payment_reference')->nullable();
            $table->text('payment_notes')->nullable();
            $table->string('payment_status')->default('posted'); // draft, posted, cancelled
            $table->string('payment_method')->nullable(); // cash, check, bank_transfer, etc.
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['supplier_id', 'payment_date']);
            $table->index(['payment_status', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
