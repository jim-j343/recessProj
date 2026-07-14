<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_invitations', function (Blueprint $table) {
            $table->id('invitation_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('invited_user_id');
            $table->unsignedBigInteger('invited_by');
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('group_id')
                  ->references('group_id')->on('groups')
                  ->onDelete('cascade');

            $table->foreign('invited_user_id')
                  ->references('user_id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('invited_by')
                  ->references('user_id')->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_invitations');
    }
};
