<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('post_id')->nullable();
            $table->unsignedBigInteger('topic_id')->nullable();
            $table->enum('type', [
                'reply',
                'warning',
                'quiz_announced',
                'blacklisted',
                'mention'
            ]);
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('post_id')
                  ->references('post_id')
                  ->on('posts')
                  ->onDelete('set null');

            $table->foreign('topic_id')
                  ->references('topic_id')
                  ->on('topics')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
