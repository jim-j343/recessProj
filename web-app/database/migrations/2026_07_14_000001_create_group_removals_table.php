<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_removals', function (Blueprint $table) {
            $table->id('removal_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('removed_user_id');
            $table->unsignedBigInteger('removed_by');
            $table->string('reason', 500)->nullable();
            $table->boolean('reviewed')->default(false);
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('group_id')
                  ->references('group_id')->on('groups')
                  ->onDelete('cascade');

            $table->foreign('removed_user_id')
                  ->references('user_id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('removed_by')
                  ->references('user_id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('reviewed_by')
                  ->references('user_id')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_removals');
    }
};
