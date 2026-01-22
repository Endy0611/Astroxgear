<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add firebase_uid column if it doesn't exist
            if (!Schema::hasColumn('users', 'firebase_uid')) {
                $table->string('firebase_uid')->nullable()->unique()->after('email');
            }
            
            // Update role enum to include 'user' or change to string
            // Option 1: Change ENUM to include 'user'
            $table->enum('role', ['admin', 'customer', 'user'])->default('customer')->change();
            
            // Option 2: If you prefer to use string instead of enum, uncomment this:
            // $table->string('role')->default('customer')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'firebase_uid')) {
                $table->dropColumn('firebase_uid');
            }
            
            // Revert role back to original
            $table->enum('role', ['admin', 'customer'])->default('customer')->change();
        });
    }
};