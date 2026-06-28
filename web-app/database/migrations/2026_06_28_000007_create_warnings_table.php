<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warnings', function (Blueprint $table) {
            $table->id('warning_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('group_id');
            $table->tinyInteger('warning_number'); // 1 or 2
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('deadline');
            $table->boolean('is_heeded')->default(false);

            $table->foreign('user_id')
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
        Schema::dropIfExists('warnings');
    }
};
