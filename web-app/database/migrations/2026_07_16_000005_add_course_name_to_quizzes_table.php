<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Safe to re-run: only adds the column if it isn't already there
        if (!Schema::hasColumn('quizzes', 'course_name')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->string('course_name', 150)->nullable()->after('title');
            });
        }

        // Making group_id nullable (for course-targeted quizzes) needs
        // driver-specific handling. SQLite (used by the test suite) has no
        // DROP FOREIGN KEY / MODIFY syntax at all, but Schema Builder's
        // native ->change() handles it there via a full table rebuild —
        // and since tests always run against a fresh :memory: DB, we don't
        // need the "is it already nullable" guard on that branch.
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->unsignedBigInteger('group_id')->nullable()->change();
            });
        } else {
            // Only touch group_id/its foreign key if it isn't already nullable
            $column = DB::select("SHOW COLUMNS FROM `quizzes` WHERE Field = 'group_id'");
            $alreadyNullable = $column && $column[0]->Null === 'YES';

            if (!$alreadyNullable) {
                $existingForeignKeys = DB::select("
                    SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'quizzes'
                    AND COLUMN_NAME = 'group_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");

                foreach ($existingForeignKeys as $fk) {
                    DB::statement("ALTER TABLE `quizzes` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                }

                DB::statement('ALTER TABLE `quizzes` MODIFY `group_id` BIGINT UNSIGNED NULL');

                // `groups` is a reserved word as of MySQL 8.0.2 (window function
                // frame syntax) — must be backtick-quoted in raw SQL, unlike
                // Schema Builder calls elsewhere which quote it automatically
                DB::statement('ALTER TABLE `quizzes` ADD CONSTRAINT `quizzes_group_id_foreign`
                    FOREIGN KEY (`group_id`) REFERENCES `groups`(`group_id`) ON DELETE SET NULL');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('quizzes', 'course_name')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropColumn('course_name');
            });
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->unsignedBigInteger('group_id')->nullable(false)->change();
            });
            return;
        }

        $existingForeignKeys = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'quizzes'
            AND COLUMN_NAME = 'group_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($existingForeignKeys as $fk) {
            DB::statement("ALTER TABLE `quizzes` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        DB::statement('ALTER TABLE `quizzes` MODIFY `group_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `quizzes` ADD CONSTRAINT `quizzes_group_id_foreign`
            FOREIGN KEY (`group_id`) REFERENCES `groups`(`group_id`) ON DELETE CASCADE');
    }
};