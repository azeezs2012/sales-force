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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->date('po_date');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('location_id')->constrained('locations');
            $table->text('po_billing_address');
            $table->text('po_delivery_address');
            $table->string('po_status')->default('Draft'); // e.g., Draft, Submitted, Approved, Completed
            $table->decimal('total_amount', 15, 2)->default(0.00);

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
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
        Schema::dropIfExists('purchase_orders');
    }
}; 