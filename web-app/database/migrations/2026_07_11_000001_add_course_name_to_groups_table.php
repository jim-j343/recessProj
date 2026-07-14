<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            // Free text like "CS301: Database Systems" — nullable so
            // existing groups aren't broken by this migration, but the
            // create-group form makes it required for anything new.
            $table->string('course_name', 150)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('course_name');
        });
    }
};
