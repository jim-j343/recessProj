<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('topic_id');
            // ML confidence score — higher means more relevant
            $table->float('score')->default(0.0);
            $table->timestamp('generated_at')->useCurrent();
            $table->boolean('is_dismissed')->default(false);

            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('topic_id')
                  ->references('topic_id')
                  ->on('topics')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_recommendations');
    }
};
