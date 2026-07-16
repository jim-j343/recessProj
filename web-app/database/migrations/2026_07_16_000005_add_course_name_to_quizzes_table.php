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

        // Only touch group_id/its foreign key if it isn't already nullable
        // — avoids re-running raw SQL against a column already fixed by an
        // earlier partial attempt
        $column = DB::select("SHOW COLUMNS FROM quizzes WHERE Field = 'group_id'");
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
                DB::statement("ALTER TABLE quizzes DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            }

            DB::statement('ALTER TABLE quizzes MODIFY group_id BIGINT UNSIGNED NULL');

            DB::statement('ALTER TABLE quizzes ADD CONSTRAINT quizzes_group_id_foreign
                FOREIGN KEY (group_id) REFERENCES groups(group_id) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('quizzes', 'course_name')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropColumn('course_name');
            });
        }

        $existingForeignKeys = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'quizzes'
            AND COLUMN_NAME = 'group_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($existingForeignKeys as $fk) {
            DB::statement("ALTER TABLE quizzes DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
        }

        DB::statement('ALTER TABLE quizzes MODIFY group_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE quizzes ADD CONSTRAINT quizzes_group_id_foreign
            FOREIGN KEY (group_id) REFERENCES groups(group_id) ON DELETE CASCADE');
    }
};
