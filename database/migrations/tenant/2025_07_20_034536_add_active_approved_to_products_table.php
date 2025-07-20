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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('inventory_account_id');
            $table->boolean('approved')->default(false)->after('active');
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['active', 'approved', 'approved_by']);
        });
    }
};
