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
        if (! Schema::hasColumn('area_hiking_route', 'percentage')) {
            Schema::table('area_hiking_route', function (Blueprint $table) {
                $table->float('percentage')->default(0);
            });
        }
        if (! Schema::hasColumn('ec_track_province', 'percentage')) {
            Schema::table('ec_track_province', function (Blueprint $table) {
                $table->float('percentage')->default(0);
            });
        }
        if (! Schema::hasColumn('ec_track_region', 'percentage')) {
            Schema::table('ec_track_region', function (Blueprint $table) {
                $table->float('percentage')->default(0);
            });
        }
        if (! Schema::hasColumn('ec_track_sector', 'percentage')) {
            Schema::table('ec_track_sector', function (Blueprint $table) {
                $table->float('percentage')->default(0);
            });
        }

        if (! Schema::hasColumn('mountain_group_region', 'percentage')) {
            Schema::table('mountain_group_region', function (Blueprint $table) {
                $table->float('percentage')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('area_hiking_route', function (Blueprint $table) {
            $table->dropColumn('percentage');
        });
        Schema::table('ec_track_province', function (Blueprint $table) {
            $table->dropColumn('percentage');
        });
        Schema::table('ec_track_region', function (Blueprint $table) {
            $table->dropColumn('percentage');
        });
        Schema::table('ec_track_sector', function (Blueprint $table) {
            $table->dropColumn('percentage');
        });
        Schema::table('mountain_group_region', function (Blueprint $table) {
            $table->dropColumn('percentage');
        });
    }
};
