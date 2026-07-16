<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_exclusions', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('excluded_user_id');
            $table->primary(['post_id', 'excluded_user_id']);

            $table->foreign('post_id')
                  ->references('post_id')->on('posts')
                  ->onDelete('cascade');

            $table->foreign('excluded_user_id')
                  ->references('user_id')->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_exclusions');
    }
};
