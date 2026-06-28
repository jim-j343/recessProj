<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('group_id')->nullable();
            $table->text('content');
            $table->timestamps();

            $table->foreign('sender_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('group_id')
                  ->references('group_id')
                  ->on('groups')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_messages');
    }
};
