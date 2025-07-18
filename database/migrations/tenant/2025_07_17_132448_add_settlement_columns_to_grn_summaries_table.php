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
        Schema::table('grn_summaries', function (Blueprint $table) {
            // Settlement tracking columns
            $table->decimal('total_payment_received_amount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('total_grn_credit_settled_amount', 15, 2)->default(0)->after('total_payment_received_amount');
            $table->decimal('total_settled_amount', 15, 2)->default(0)->after('total_grn_credit_settled_amount');
            
            // Update grn_status to include more statuses
            $table->string('grn_status')->default('Open')->change(); // Open, Partial, Closed, Cancelled
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grn_summaries', function (Blueprint $table) {
            $table->dropColumn([
                'total_payment_received_amount',
                'total_grn_credit_settled_amount', 
                'total_settled_amount'
            ]);
        });
    }
};
