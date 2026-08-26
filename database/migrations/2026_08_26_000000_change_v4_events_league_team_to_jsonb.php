<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// league/team were single strings; events now support multiple (parity with
// profile chips input). Convert to jsonb arrays, wrapping any existing scalar
// value as a one-element array so no data is lost.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE v4_events ALTER COLUMN league TYPE jsonb USING CASE WHEN league IS NULL OR league = '' THEN NULL ELSE to_jsonb(ARRAY[league]) END");
        DB::statement("ALTER TABLE v4_events ALTER COLUMN team TYPE jsonb USING CASE WHEN team IS NULL OR team = '' THEN NULL ELSE to_jsonb(ARRAY[team]) END");
    }

    public function down(): void
    {
        // Collapse back to the first array element (lossy if multiple were set).
        DB::statement("ALTER TABLE v4_events ALTER COLUMN league TYPE varchar(255) USING (league->>0)");
        DB::statement("ALTER TABLE v4_events ALTER COLUMN team TYPE varchar(255) USING (team->>0)");
    }
};
