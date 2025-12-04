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
        Schema::create('ec_track_ec_poi', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('ec_track_id')->constrained('ec_tracks')->onDelete('set null');
            $table->foreignId('ec_poi_id')->constrained('ec_pois')->onDelete('set null');
            $table->integer('buffer')->default(250);
        });

        Schema::create('ec_poi_club', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('ec_poi_id')->constrained('ec_pois')->onDelete('set null');
            $table->foreignId('club_id')->constrained('clubs')->onDelete('set null');
        });

        Schema::create('ec_poi_cai_hut', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('ec_poi_id')->constrained('ec_pois')->onDelete('set null');
            $table->foreignId('cai_hut_id')->constrained('cai_huts')->onDelete('set null');
            $table->integer('buffer')->default(250);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ec_track_ec_poi');
        Schema::dropIfExists('ec_poi_club');
        Schema::dropIfExists('ec_poi_cai_hut');
    }
};
