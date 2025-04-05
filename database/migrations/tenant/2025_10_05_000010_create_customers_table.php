<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code');
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->string('phone_no')->nullable();
            $table->foreignId('default_payment_method')->nullable()->constrained('payment_methods');
            $table->foreignId('default_payment_term')->nullable()->constrained('payment_terms');
            $table->foreignId('default_sales_rep')->nullable()->constrained('sales_reps');
            $table->boolean('active')->default(true);
            $table->boolean('approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('parent')->nullable()->constrained('customers');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}; 