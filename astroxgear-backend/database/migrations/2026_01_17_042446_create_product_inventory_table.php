<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_inventory', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity_change');
            $table->integer('quantity_after');
            $table->enum('type', ['purchase', 'sale', 'return', 'adjustment', 'damage']);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            $table->foreign('product_id')->references('id')->on('tblproduct')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_inventory');
    }
};