<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Raw ENUM widening is MySQL-only syntax — SQLite (used by the test
        // suite) has no ENUM type and no MODIFY COLUMN, so this is a no-op
        // there; the column already accepts any string value in that driver.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
                'reply', 'warning', 'quiz_announced', 'blacklisted', 'mention', 'added_to_group'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
                'reply', 'warning', 'quiz_announced', 'blacklisted', 'mention'
            ) NOT NULL");
        }
    }
};