<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id('topic_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('creator_id');
            $table->string('title', 255);
            $table->string('category', 80)->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->timestamps();

            $table->foreign('group_id')
                  ->references('group_id')
                  ->on('groups')
                  ->onDelete('cascade');

            $table->foreign('creator_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
