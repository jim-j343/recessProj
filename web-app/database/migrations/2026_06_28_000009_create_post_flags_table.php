<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_flags', function (Blueprint $table) {
            $table->id('flag_id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('flagged_by');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'dismissed'])
                  ->default('pending');
            $table->timestamps();

            $table->foreign('post_id')
                  ->references('post_id')
                  ->on('posts')
                  ->onDelete('cascade');

            $table->foreign('flagged_by')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_flags');
    }
};
