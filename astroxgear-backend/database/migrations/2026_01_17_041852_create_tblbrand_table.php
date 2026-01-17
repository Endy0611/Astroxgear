<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblbrand', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name');
            $table->string('brand_slug')->unique();
            $table->text('brand_description')->nullable();
            $table->string('brand_logo')->nullable();
            $table->string('brand_website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblbrand');
    }
};