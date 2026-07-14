<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('attachment')->nullable()->after('content');
            $table->string('attachment_type')->nullable()->after('attachment');
            $table->string('attachment_name')->nullable()->after('attachment_type');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['attachment', 'attachment_type', 'attachment_name']);
        });
    }
};
