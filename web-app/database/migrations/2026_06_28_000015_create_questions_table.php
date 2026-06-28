<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id('question_id');
            $table->unsignedBigInteger('quiz_id');
            $table->text('content');
            $table->enum('type', ['mcq', 'short_answer']);
            $table->decimal('marks', 4, 1)->default(1.0);
            $table->integer('order_index')->default(0);

            $table->foreign('quiz_id')
                  ->references('quiz_id')
                  ->on('quizzes')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
