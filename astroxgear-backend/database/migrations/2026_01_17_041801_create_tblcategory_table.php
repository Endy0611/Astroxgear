<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblcategory', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->string('category_slug')->unique();
            $table->text('category_description')->nullable();
            $table->string('category_image')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('tblcategory')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblcategory');
    }
};