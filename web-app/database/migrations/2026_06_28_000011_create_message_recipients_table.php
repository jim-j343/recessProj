<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_recipients', function (Blueprint $table) {
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('recipient_id');
            $table->primary(['message_id', 'recipient_id']);

            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->foreign('message_id')
                  ->references('message_id')
                  ->on('private_messages')
                  ->onDelete('cascade');

            $table->foreign('recipient_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_recipients');
    }
};
