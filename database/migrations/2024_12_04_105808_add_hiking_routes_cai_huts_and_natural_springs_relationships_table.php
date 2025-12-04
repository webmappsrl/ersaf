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
        // remove nearby_cai_hut and nearby_natural_spring columns from ec_tracks table
        Schema::table('ec_tracks', function (Blueprint $table) {
            $table->dropColumn('nearby_cai_huts');
            $table->dropColumn('nearby_natural_springs');
        });

        Schema::create('ec_track_cai_hut', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('ec_track_id')->constrained('ec_tracks')->onDelete('set null');
            $table->foreignId('cai_hut_id')->constrained('cai_huts')->onDelete('set null');
            $table->integer('buffer')->default(250);
        });

        Schema::create('ec_track_natural_spring', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('ec_track_id')->constrained('ec_tracks')->onDelete('set null');
            $table->foreignId('natural_spring_id')->constrained('natural_springs')->onDelete('set null');
            $table->integer('buffer')->default(250);
        });

        // add timestamps to hiking_route relationships tables
        if (! Schema::hasColumn('area_hiking_route', 'created_at')) {
            Schema::table('area_hiking_route', function (Blueprint $table) {
                $table->timestamps();
            });
        }
        if (! Schema::hasColumn('ec_track_province', 'created_at')) {
            Schema::table('ec_track_province', function (Blueprint $table) {
                $table->timestamps();
            });
        }
        if (! Schema::hasColumn('ec_track_region', 'created_at')) {
            Schema::table('ec_track_region', function (Blueprint $table) {
                $table->timestamps();
            });
        }
        if (! Schema::hasColumn('ec_track_sector', 'created_at')) {
            Schema::table('ec_track_sector', function (Blueprint $table) {
                $table->timestamps();
            });
        }
        if (! Schema::hasColumn('ec_track_club', 'created_at')) {
            Schema::table('ec_track_club', function (Blueprint $table) {
                $table->timestamps();
            });
        }
        if (! Schema::hasColumn('ec_track_itinerary', 'created_at')) {
            Schema::table('ec_track_itinerary', function (Blueprint $table) {
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('mountain_group_region', 'created_at')) {
            Schema::table('mountain_group_region', function (Blueprint $table) {
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // add nearby_cai_hut and nearby_natural_spring columns to ec_tracks table
        Schema::table('ec_tracks', function (Blueprint $table) {
            $table->string('nearby_cai_huts')->nullable();
            $table->string('nearby_natural_springs')->nullable();
        });
        Schema::dropIfExists('ec_track_cai_hut');
        Schema::dropIfExists('ec_track_natural_spring');
        Schema::dropColumns('area_hiking_route', ['created_at', 'updated_at']);
        Schema::dropColumns('ec_track_province', ['created_at', 'updated_at']);
        Schema::dropColumns('ec_track_region', ['created_at', 'updated_at']);
        Schema::dropColumns('ec_track_sector', ['created_at', 'updated_at']);
        Schema::dropColumns('ec_track_club', ['created_at', 'updated_at']);
        Schema::dropColumns('ec_track_itinerary', ['created_at', 'updated_at']);
        Schema::dropColumns('mountain_group_region', ['created_at', 'updated_at']);
    }
};
