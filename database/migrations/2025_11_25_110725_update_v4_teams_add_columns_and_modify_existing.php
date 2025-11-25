<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('v4_teams', function (Blueprint $table) {
            // New columns
            $table->string('phone')->nullable()->after('team_name');
            $table->string('city')->nullable()->after('phone');
            $table->string('state')->nullable()->after('city');
            $table->string('zipcode')->nullable()->after('state');
            $table->string('country')->nullable()->after('zipcode');
        });

        // ⚠️ Modify existing columns using raw SQL for Postgres
        DB::statement('ALTER TABLE v4_teams ALTER COLUMN leagues TYPE JSON USING leagues::json');
        DB::statement('ALTER TABLE v4_teams ALTER COLUMN team_years_running TYPE INTEGER USING team_years_running::integer');
    }


    public function down(): void
    {
        // Drop new columns
        Schema::table('v4_teams', function (Blueprint $table) {
            $table->dropColumn(['phone', 'city', 'state', 'zipcode', 'country']);
        });

        // Revert JSON → text
        DB::statement('ALTER TABLE v4_teams ALTER COLUMN leagues TYPE TEXT USING leagues::text');

        // Revert integer → text
        DB::statement('ALTER TABLE v4_teams ALTER COLUMN team_years_running TYPE TEXT USING team_years_running::text');
    }

};
