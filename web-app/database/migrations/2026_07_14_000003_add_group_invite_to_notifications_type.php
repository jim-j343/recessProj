<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
            'reply', 'warning', 'quiz_announced', 'blacklisted', 'mention', 'group_invite'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
            'reply', 'warning', 'quiz_announced', 'blacklisted', 'mention'
        ) NOT NULL");
    }
};
