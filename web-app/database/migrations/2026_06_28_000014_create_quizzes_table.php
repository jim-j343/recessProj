<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id('quiz_id');
            $table->unsignedBigInteger('lecturer_id');
            $table->unsignedBigInteger('group_id');
            $table->string('title', 255);
            $table->string('target_category', 80)->nullable();
            $table->dateTime('start_time');
            $table->integer('duration_minutes');
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->foreign('lecturer_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('group_id')
                  ->references('group_id')
                  ->on('groups')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
