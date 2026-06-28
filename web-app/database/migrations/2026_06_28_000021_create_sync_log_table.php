<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_log', function (Blueprint $table) {
            $table->id('sync_id');
            // which table the synced record belongs to e.g. 'posts'
            $table->string('table_name', 60);
            // primary key of the record in the local SQLite database
            $table->unsignedBigInteger('local_id');
            // primary key assigned by MySQL after sync — null until synced
            $table->unsignedBigInteger('server_id')->nullable();
            $table->enum('status', ['pending', 'synced', 'conflict', 'failed'])
                  ->default('pending');
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_log');
    }
};
