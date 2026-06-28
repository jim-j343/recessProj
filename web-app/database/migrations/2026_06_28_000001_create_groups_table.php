<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id('group_id');
            $table->unsignedBigInteger('admin_id');
            $table->string('name', 120)->unique();
            $table->text('description')->nullable();
            $table->integer('inactivity_warning_days')->default(7);
            $table->integer('blacklist_duration_days')->default(30);
            $table->timestamps();

            $table->foreign('admin_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
