<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('username', 80)->unique();
            $table->string('email', 150)->unique();
            $table->string('password_hash', 255);
            $table->enum('system_role', ['student', 'lecturer', 'system_admin'])
                  ->default('student');
            $table->enum('status', ['active', 'blacklisted', 'suspended'])
                  ->default('active');
            $table->boolean('agreed_to_rules')->default(false);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps(); // creates created_at and updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
