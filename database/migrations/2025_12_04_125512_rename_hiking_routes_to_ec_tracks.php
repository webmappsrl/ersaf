<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rinomina la tabella principale
        DB::statement('ALTER TABLE hiking_routes RENAME TO ec_tracks');

        // Rinomina le tabelle pivot
        DB::statement('ALTER TABLE hiking_route_cai_hut RENAME TO ec_track_cai_hut');
        DB::statement('ALTER TABLE hiking_route_club RENAME TO ec_track_club');
        DB::statement('ALTER TABLE hiking_route_ec_poi RENAME TO ec_track_ec_poi');
        DB::statement('ALTER TABLE hiking_route_itinerary RENAME TO ec_track_itinerary');
        DB::statement('ALTER TABLE hiking_route_natural_spring RENAME TO ec_track_natural_spring');
        DB::statement('ALTER TABLE hiking_route_province RENAME TO ec_track_province');
        DB::statement('ALTER TABLE hiking_route_region RENAME TO ec_track_region');
        DB::statement('ALTER TABLE hiking_route_sector RENAME TO ec_track_sector');
        DB::statement('ALTER TABLE area_hiking_route RENAME TO area_ec_track');
        DB::statement('ALTER TABLE mountain_group_hiking_route RENAME TO mountain_group_ec_track');

        // Rinomina le colonne hiking_route_id in ec_track_id
        DB::statement('ALTER TABLE ec_track_cai_hut RENAME COLUMN hiking_route_id TO ec_track_id');
        DB::statement('ALTER TABLE ec_track_club RENAME COLUMN hiking_route_id TO ec_track_id');
        DB::statement('ALTER TABLE ec_track_ec_poi RENAME COLUMN hiking_route_id TO ec_track_id');
        DB::statement('ALTER TABLE ec_track_itinerary RENAME COLUMN hiking_route_id TO ec_track_id');
        DB::statement('ALTER TABLE ec_track_natural_spring RENAME COLUMN hiking_route_id TO ec_track_id');
        DB::statement('ALTER TABLE ec_track_province RENAME COLUMN hiking_route_id TO ec_track_id');
        DB::statement('ALTER TABLE ec_track_region RENAME COLUMN hiking_route_id TO ec_track_id');
        DB::statement('ALTER TABLE ec_track_sector RENAME COLUMN hiking_route_id TO ec_track_id');
        DB::statement('ALTER TABLE area_ec_track RENAME COLUMN hiking_route_id TO ec_track_id');
        DB::statement('ALTER TABLE mountain_group_ec_track RENAME COLUMN hiking_route_id TO ec_track_id');

        // Aggiungi colonne hiking_route_id come alias di ec_track_id per compatibilità con Laravel
        // Queste colonne saranno generate columns che puntano a ec_track_id
        DB::statement('ALTER TABLE ec_track_cai_hut ADD COLUMN hiking_route_id BIGINT GENERATED ALWAYS AS (ec_track_id) STORED');
        DB::statement('ALTER TABLE ec_track_club ADD COLUMN hiking_route_id BIGINT GENERATED ALWAYS AS (ec_track_id) STORED');
        DB::statement('ALTER TABLE ec_track_ec_poi ADD COLUMN hiking_route_id BIGINT GENERATED ALWAYS AS (ec_track_id) STORED');
        DB::statement('ALTER TABLE ec_track_itinerary ADD COLUMN hiking_route_id BIGINT GENERATED ALWAYS AS (ec_track_id) STORED');
        DB::statement('ALTER TABLE ec_track_natural_spring ADD COLUMN hiking_route_id BIGINT GENERATED ALWAYS AS (ec_track_id) STORED');
        DB::statement('ALTER TABLE ec_track_province ADD COLUMN hiking_route_id BIGINT GENERATED ALWAYS AS (ec_track_id) STORED');
        DB::statement('ALTER TABLE ec_track_region ADD COLUMN hiking_route_id BIGINT GENERATED ALWAYS AS (ec_track_id) STORED');
        DB::statement('ALTER TABLE ec_track_sector ADD COLUMN hiking_route_id BIGINT GENERATED ALWAYS AS (ec_track_id) STORED');
        DB::statement('ALTER TABLE area_ec_track ADD COLUMN hiking_route_id BIGINT GENERATED ALWAYS AS (ec_track_id) STORED');
        DB::statement('ALTER TABLE mountain_group_ec_track ADD COLUMN hiking_route_id BIGINT GENERATED ALWAYS AS (ec_track_id) STORED');

        // Rinomina i constraint (foreign keys, primary keys, ecc.)
        $this->renameConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi le colonne generate hiking_route_id
        DB::statement('ALTER TABLE ec_track_cai_hut DROP COLUMN IF EXISTS hiking_route_id');
        DB::statement('ALTER TABLE ec_track_club DROP COLUMN IF EXISTS hiking_route_id');
        DB::statement('ALTER TABLE ec_track_ec_poi DROP COLUMN IF EXISTS hiking_route_id');
        DB::statement('ALTER TABLE ec_track_itinerary DROP COLUMN IF EXISTS hiking_route_id');
        DB::statement('ALTER TABLE ec_track_natural_spring DROP COLUMN IF EXISTS hiking_route_id');
        DB::statement('ALTER TABLE ec_track_province DROP COLUMN IF EXISTS hiking_route_id');
        DB::statement('ALTER TABLE ec_track_region DROP COLUMN IF EXISTS hiking_route_id');
        DB::statement('ALTER TABLE ec_track_sector DROP COLUMN IF EXISTS hiking_route_id');
        DB::statement('ALTER TABLE area_ec_track DROP COLUMN IF EXISTS hiking_route_id');
        DB::statement('ALTER TABLE mountain_group_ec_track DROP COLUMN IF EXISTS hiking_route_id');

        // Rinomina le colonne ec_track_id in hiking_route_id
        DB::statement('ALTER TABLE ec_track_cai_hut RENAME COLUMN ec_track_id TO hiking_route_id');
        DB::statement('ALTER TABLE ec_track_club RENAME COLUMN ec_track_id TO hiking_route_id');
        DB::statement('ALTER TABLE ec_track_ec_poi RENAME COLUMN ec_track_id TO hiking_route_id');
        DB::statement('ALTER TABLE ec_track_itinerary RENAME COLUMN ec_track_id TO hiking_route_id');
        DB::statement('ALTER TABLE ec_track_natural_spring RENAME COLUMN ec_track_id TO hiking_route_id');
        DB::statement('ALTER TABLE ec_track_province RENAME COLUMN ec_track_id TO hiking_route_id');
        DB::statement('ALTER TABLE ec_track_region RENAME COLUMN ec_track_id TO hiking_route_id');
        DB::statement('ALTER TABLE ec_track_sector RENAME COLUMN ec_track_id TO hiking_route_id');
        DB::statement('ALTER TABLE area_ec_track RENAME COLUMN ec_track_id TO hiking_route_id');
        DB::statement('ALTER TABLE mountain_group_ec_track RENAME COLUMN ec_track_id TO hiking_route_id');

        // Rinomina le tabelle pivot
        DB::statement('ALTER TABLE ec_track_cai_hut RENAME TO hiking_route_cai_hut');
        DB::statement('ALTER TABLE ec_track_club RENAME TO hiking_route_club');
        DB::statement('ALTER TABLE ec_track_ec_poi RENAME TO hiking_route_ec_poi');
        DB::statement('ALTER TABLE ec_track_itinerary RENAME TO hiking_route_itinerary');
        DB::statement('ALTER TABLE ec_track_natural_spring RENAME TO hiking_route_natural_spring');
        DB::statement('ALTER TABLE ec_track_province RENAME TO hiking_route_province');
        DB::statement('ALTER TABLE ec_track_region RENAME TO hiking_route_region');
        DB::statement('ALTER TABLE ec_track_sector RENAME TO hiking_route_sector');
        DB::statement('ALTER TABLE area_ec_track RENAME TO area_hiking_route');
        DB::statement('ALTER TABLE mountain_group_ec_track RENAME TO mountain_group_hiking_route');

        // Rinomina la tabella principale
        DB::statement('ALTER TABLE ec_tracks RENAME TO hiking_routes');

        // Ripristina i constraint
        $this->renameConstraints(true);
    }

    /**
     * Rinomina i constraint che contengono "hiking_route" in "ec_track"
     */
    private function renameConstraints(bool $reverse = false): void
    {
        $constraints = DB::select("
            SELECT tc.constraint_name, tc.table_name
            FROM information_schema.table_constraints tc
            WHERE tc.constraint_schema = 'public'
            AND tc.constraint_name LIKE '%hiking_route%'
            AND (
                tc.table_name LIKE 'ec_track_%' 
                OR tc.table_name = 'ec_tracks'
                OR tc.table_name = 'area_ec_track'
                OR tc.table_name = 'mountain_group_ec_track'
            )
        ");

        foreach ($constraints as $constraint) {
            $oldName = $constraint->constraint_name;
            $newName = $reverse
                ? str_replace('ec_track', 'hiking_route', $oldName)
                : str_replace('hiking_route', 'ec_track', $oldName);

            if ($oldName !== $newName) {
                try {
                    DB::statement("ALTER TABLE {$constraint->table_name} RENAME CONSTRAINT \"{$oldName}\" TO \"{$newName}\"");
                } catch (\Exception $e) {
                    // Ignora errori se il constraint non esiste o è già stato rinominato
                }
            }
        }
    }
};
