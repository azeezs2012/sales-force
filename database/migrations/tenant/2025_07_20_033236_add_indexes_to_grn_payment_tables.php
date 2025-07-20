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
        // Add indexes to grn_settlements table
        Schema::table('grn_settlements', function (Blueprint $table) {
            // Composite index for settlement lookups
            $table->index(['settlement_reference_type', 'settlement_reference_id'], 'idx_settlement_reference');
            
            // Index for GRN summary lookups
            $table->index('grn_summary_id', 'idx_grn_settlement_summary');
            
            // Index for settlement type filtering
            $table->index('settlement_type', 'idx_settlement_type');
            
            // Index for date range queries
            $table->index('settlement_date', 'idx_settlement_date');
        });

        // Add indexes to grn_credit_settlements table
        Schema::table('grn_credit_settlements', function (Blueprint $table) {
            // Composite index for settlement lookups
            $table->index(['settlement_reference_type', 'settlement_reference_id'], 'idx_credit_settlement_reference');
            
            // Index for GRN credit summary lookups
            $table->index('grn_credit_summary_id', 'idx_credit_settlement_summary');
            
            // Index for settlement type filtering
            $table->index('settlement_type', 'idx_credit_settlement_type');
            
            // Index for date range queries
            $table->index('settlement_date', 'idx_credit_settlement_date');
        });

        // Add indexes to payments table
        Schema::table('payments', function (Blueprint $table) {
            // Index for supplier lookups
            $table->index('supplier_id', 'idx_payment_supplier');
            
            // Index for date range queries
            $table->index('payment_date', 'idx_payment_date');
            
            // Index for status filtering
            $table->index('payment_status', 'idx_payment_status');
        });

        // Add indexes to grn_summaries table
        Schema::table('grn_summaries', function (Blueprint $table) {
            // Index for supplier lookups
            $table->index('supplier_id', 'idx_grn_supplier');
            
            // Index for status filtering
            $table->index('grn_status', 'idx_grn_status');
            
            // Index for date range queries
            $table->index('grn_date', 'idx_grn_date');
        });

        // Add indexes to grn_credit_summaries table
        Schema::table('grn_credit_summaries', function (Blueprint $table) {
            // Index for supplier lookups
            $table->index('supplier_id', 'idx_grn_credit_supplier');
            
            // Index for status filtering
            $table->index('grn_credit_status', 'idx_grn_credit_status');
            
            // Index for date range queries
            $table->index('grn_credit_date', 'idx_grn_credit_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from grn_settlements table
        Schema::table('grn_settlements', function (Blueprint $table) {
            $table->dropIndex('idx_settlement_reference');
            $table->dropIndex('idx_grn_settlement_summary');
            $table->dropIndex('idx_settlement_type');
            $table->dropIndex('idx_settlement_date');
        });

        // Remove indexes from grn_credit_settlements table
        Schema::table('grn_credit_settlements', function (Blueprint $table) {
            $table->dropIndex('idx_credit_settlement_reference');
            $table->dropIndex('idx_credit_settlement_summary');
            $table->dropIndex('idx_credit_settlement_type');
            $table->dropIndex('idx_credit_settlement_date');
        });

        // Remove indexes from payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payment_supplier');
            $table->dropIndex('idx_payment_date');
            $table->dropIndex('idx_payment_status');
        });

        // Remove indexes from grn_summaries table
        Schema::table('grn_summaries', function (Blueprint $table) {
            $table->dropIndex('idx_grn_supplier');
            $table->dropIndex('idx_grn_status');
            $table->dropIndex('idx_grn_date');
        });

        // Remove indexes from grn_credit_summaries table
        Schema::table('grn_credit_summaries', function (Blueprint $table) {
            $table->dropIndex('idx_grn_credit_supplier');
            $table->dropIndex('idx_grn_credit_status');
            $table->dropIndex('idx_grn_credit_date');
        });
    }
};
