<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblproduct', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('product_slug')->unique();
            $table->string('sku')->unique();
            $table->text('product_description');
            $table->text('short_description')->nullable();
            
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('brand_id');
            
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->integer('discount_percentage')->default(0);
            
            $table->string('product_image')->nullable();
            $table->json('product_gallery')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('dimensions')->nullable();
            $table->json('specifications')->nullable();
            
            $table->integer('stock_quantity')->default(0);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'pre_order'])->default('in_stock');
            
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('views')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            
            $table->timestamps();
            
            $table->foreign('category_id')->references('id')->on('tblcategory')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('tblbrand')->onDelete('cascade');
            
            $table->index('category_id');
            $table->index('brand_id');
            $table->index('is_featured');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblproduct');
    }
};