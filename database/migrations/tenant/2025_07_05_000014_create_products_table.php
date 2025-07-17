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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->text('product_description')->nullable();
            $table->enum('inventory_type', ['Service', 'Inventory']);
            $table->foreignId('parent_id')->nullable()->constrained('products');
            $table->foreignId('product_type_id')->nullable()->constrained('product_types');
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories');
            $table->decimal('cost', 15, 2)->default(0.00);
            $table->decimal('price', 15, 2)->default(0.00);
            $table->foreignId('sales_account_id')->constrained('accounts');
            $table->foreignId('expense_account_id')->constrained('accounts');
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_name', 'inventory_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}; 