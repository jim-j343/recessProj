<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_rules', function (Blueprint $table) {
            $table->id('rule_id');
            $table->unsignedBigInteger('group_id');
            $table->text('content');
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->foreign('group_id')
                  ->references('group_id')
                  ->on('groups')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_rules');
    }
};
