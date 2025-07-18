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
        Schema::create('grn_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_summary_id')->constrained('grn_summaries')->onDelete('cascade');
            
            // Settlement type: 'payment' or 'grn_credit'
            $table->enum('settlement_type', ['payment', 'grn_credit']);
            
            // Reference to the payment or GRN credit that settled this GRN
            $table->unsignedBigInteger('settlement_reference_id');
            $table->string('settlement_reference_type'); // 'payments' or 'grn_credit_summaries'
            
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
            $table->index(['grn_summary_id', 'settlement_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grn_settlements');
    }
};
