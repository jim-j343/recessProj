<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blacklist', function (Blueprint $table) {
            $table->id('blacklist_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('group_id');
            $table->text('reason')->nullable();
            $table->timestamp('blacklisted_at')->useCurrent();
            $table->timestamp('expires_at');
            // admin who lifted early — nullable if auto-expired
            $table->unsignedBigInteger('lifted_by')->nullable();

            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('group_id')
                  ->references('group_id')
                  ->on('groups')
                  ->onDelete('cascade');

            $table->foreign('lifted_by')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklist');
    }
};
