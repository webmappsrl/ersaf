<?php

namespace App\Models;

use App\Jobs\CheckNearbyHikingRoutesJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NaturalSpring extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted()
    {
        static::saved(function ($spring) {
            if ($spring->isDirty('geometry') && ! $spring->wasRecentlyCreated) {
                CheckNearbyHikingRoutesJob::dispatch($spring, config('osm2cai.ec_track_buffer'))->onQueue('geometric-computations');
            }
        });
    }

    public function nearbyHikingRoutes()
    {
        return $this->belongsToMany(HikingRoute::class, 'ec_track_natural_spring')->withPivot(['buffer']);
    }
}
