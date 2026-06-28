<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->unsignedBigInteger('question_id');
            // nullable for short_answer questions
            $table->unsignedBigInteger('answer_id')->nullable();
            // nullable for mcq questions
            $table->text('text_response')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('marks_awarded', 4, 1)->nullable();

            $table->foreign('submission_id')
                  ->references('submission_id')
                  ->on('submissions')
                  ->onDelete('cascade');

            $table->foreign('question_id')
                  ->references('question_id')
                  ->on('questions')
                  ->onDelete('cascade');

            $table->foreign('answer_id')
                  ->references('answer_id')
                  ->on('answers')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_answers');
    }
};
