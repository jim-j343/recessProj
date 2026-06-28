<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id('post_id');
            $table->unsignedBigInteger('topic_id');
            $table->unsignedBigInteger('author_id');
            // self-referencing FK for replies
            $table->unsignedBigInteger('parent_post_id')->nullable();
            $table->text('content');
            $table->boolean('is_flagged')->default(false);
            $table->boolean('is_synced')->default(false);
            $table->timestamps();

            $table->foreign('topic_id')
                  ->references('topic_id')
                  ->on('topics')
                  ->onDelete('cascade');

            $table->foreign('author_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');

            // self-reference: a reply points to its parent post
            $table->foreign('parent_post_id')
                  ->references('post_id')
                  ->on('posts')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
