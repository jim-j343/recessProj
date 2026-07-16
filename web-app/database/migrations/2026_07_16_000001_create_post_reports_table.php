<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('reported_by');
            $table->string('reason', 500);
            $table->boolean('reviewed')->default(false);
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('post_id')
                  ->references('post_id')->on('posts')
                  ->onDelete('cascade');

            $table->foreign('reported_by')
                  ->references('user_id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('reviewed_by')
                  ->references('user_id')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_reports');
    }
};
