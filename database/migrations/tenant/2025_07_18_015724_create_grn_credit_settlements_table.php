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
        Schema::create('grn_credit_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_credit_summary_id')->constrained('grn_credit_summaries')->onDelete('cascade');
            
            // Settlement type: 'payment_applied'
            $table->enum('settlement_type', ['payment_applied'])->default('payment_applied');
            
            // Reference to the payment that applied this GRN credit
            $table->unsignedBigInteger('settlement_reference_id');
            $table->string('settlement_reference_type')->default('payments'); // 'payments'
            
            // Settlement details
            $table->decimal('settlement_amount', 15, 2);
            $table->date('settlement_date');
            $table->text('settlement_notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['settlement_reference_type', 'settlement_reference_id']);
            $table->index(['grn_credit_summary_id', 'settlement_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grn_credit_settlements');
    }
};
