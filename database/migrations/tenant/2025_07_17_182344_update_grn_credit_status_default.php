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
        Schema::table('grn_credit_summaries', function (Blueprint $table) {
            $table->string('grn_credit_status')->default('Open')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grn_credit_summaries', function (Blueprint $table) {
            $table->string('grn_credit_status')->default('draft')->change();
        });
    }
};
