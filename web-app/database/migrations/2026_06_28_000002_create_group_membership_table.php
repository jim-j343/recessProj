<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_membership', function (Blueprint $table) {
            // Composite primary key (user + group)
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('group_id');
            $table->primary(['user_id', 'group_id']);

            $table->enum('role', ['member', 'moderator', 'admin'])
                  ->default('member');
            $table->enum('status', ['active', 'blacklisted', 'pending'])
                  ->default('pending');
            $table->timestamp('joined_at')->useCurrent();

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
        Schema::dropIfExists('group_membership');
    }
};
