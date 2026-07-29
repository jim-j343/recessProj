<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Same MySQL-only ENUM widening as the added_to_group migration —
        // no-op on SQLite (test suite), which has no ENUM/MODIFY COLUMN.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
                'reply', 'warning', 'quiz_announced', 'blacklisted', 'mention', 'added_to_group', 'report_filed'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
                'reply', 'warning', 'quiz_announced', 'blacklisted', 'mention', 'added_to_group'
            ) NOT NULL");
        }
    }
};
