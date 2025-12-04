<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hiking Routes
        DB::statement('CREATE INDEX IF NOT EXISTS ec_tracks_geometry_gix ON ec_tracks USING GIST (geometry);');
        // Poles
        DB::statement('CREATE INDEX IF NOT EXISTS poles_geometry_gix ON poles USING GIST (geometry);');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ec_tracks_geometry_gix;');
        DB::statement('DROP INDEX IF EXISTS poles_geometry_gix;');
    }
};
