<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // relation areas to provinces
        Schema::table('areas', function (Blueprint $table) {
            $table->unsignedBigInteger('province_id')->nullable();
            $table->foreign('province_id')->references('id')->on('provinces');
        });

        // relation cai_huts to regions
        Schema::table('cai_huts', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable();
            $table->foreign('region_id')->references('id')->on('regions');
        });

        // relation ec_pois to regions
        Schema::table('ec_pois', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable();
            $table->foreign('region_id')->references('id')->on('regions');
        });

        // relation clubs to regions
        Schema::table('clubs', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable();
            $table->foreign('region_id')->references('id')->on('regions');
        });

        // create table itineraries
        Schema::create('itineraries', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->nullable();
            $table->jsonb('edges')->nullable();
            $table->integer('osm_id')->nullable();
            $table->string('ref')->nullable();
            $table->geography('geometry', 'MultiLineString', 4326)->nullable();
        });

        // add tech fields to mountain_groups
        Schema::table('mountain_groups', function (Blueprint $table) {
            $table->integer('elevation_min')->nullable();
            $table->integer('elevation_max')->nullable();
            $table->integer('elevation_avg')->nullable();
            $table->integer('elevation_stddev')->nullable();
            $table->integer('slope_min')->nullable();
            $table->integer('slope_max')->nullable();
            $table->integer('slope_avg')->nullable();
            $table->integer('slope_stddev')->nullable();
        });

        // add user_id for validator
        Schema::table('ec_tracks', function (Blueprint $table) {
            $table->unsignedBigInteger('validator_id')->nullable();
            $table->foreign('validator_id')->references('id')->on('users');
        });

        // relation hiking routes to itineraries
        Schema::create('ec_track_itinerary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ec_track_id')->constrained('ec_tracks');
            $table->foreignId('itinerary_id')->constrained('itineraries');
        });

        // relation hiking routes to provinces
        Schema::create('ec_track_province', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ec_track_id')->constrained('ec_tracks');
            $table->foreignId('province_id')->constrained('provinces');
        });

        // relation hiking routes to clubs
        Schema::create('ec_track_club', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ec_track_id')->constrained('ec_tracks');
            $table->foreignId('club_id')->constrained('clubs');
        });

        // relation hiking routes to regions
        Schema::create('ec_track_region', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ec_track_id')->constrained('ec_tracks');
            $table->foreignId('region_id')->constrained('regions');
        });

        // relation hiking routes to sectors
        Schema::create('ec_track_sector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ec_track_id')->constrained('ec_tracks');
            $table->foreignId('sector_id')->constrained('sectors');
            $table->float('percentage')->nullable();
        });

        // relation mountain groups to regions
        Schema::create('mountain_group_region', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mountain_group_id')->constrained('mountain_groups');
            $table->foreignId('region_id')->constrained('regions');
        });

        // relation province to regions
        Schema::table('provinces', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable();
            $table->foreign('region_id')->references('id')->on('regions');
        });

        // relation area to sectors
        Schema::table('sectors', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable();
            $table->foreign('area_id')->references('id')->on('areas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key and column from sectors table
        Schema::table('sectors', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropColumn('area_id');
        });

        // Drop foreign key and column from provinces table
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });

        // Drop foreign key and column from clubs table
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });

        // Drop mountain_group_region table
        Schema::dropIfExists('mountain_group_region');

        // Drop ec_track_sector table
        Schema::dropIfExists('ec_track_sector');

        // Drop ec_track_region table
        Schema::dropIfExists('ec_track_region');

        // Drop ec_track_club table
        Schema::dropIfExists('ec_track_club');

        // Drop ec_track_province table
        Schema::dropIfExists('ec_track_province');

        // Drop ec_track_itinerary table
        Schema::dropIfExists('ec_track_itinerary');

        // Drop tech fields from mountain_groups table
        Schema::table('mountain_groups', function (Blueprint $table) {
            $table->dropColumn([
                'elevation_min',
                'elevation_max',
                'elevation_avg',
                'elevation_stddev',
                'slope_min',
                'slope_max',
                'slope_avg',
                'slope_stddev',
            ]);
        });

        // Drop itineraries table
        Schema::dropIfExists('itineraries');

        // Drop foreign key and column from ec_pois table
        Schema::table('ec_pois', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });

        // Drop foreign key and column from cai_huts table
        Schema::table('cai_huts', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });

        // Drop foreign key and column from areas table
        Schema::table('areas', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropColumn('province_id');
        });

        // Drop foreign key and column from ec_tracks table
        Schema::table('ec_tracks', function (Blueprint $table) {
            $table->dropForeign(['validator_id']);
            $table->dropColumn('validator_id');
        });
    }
};
