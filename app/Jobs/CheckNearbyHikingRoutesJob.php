<?php

namespace App\Jobs;

class CheckNearbyHikingRoutesJob extends CheckNearbyEntitiesJob
{
    protected function getTargetTableName(): string
    {
        return 'ec_tracks';
    }

    protected function getRelationshipMethod(): string
    {
        return 'nearbyHikingRoutes';
    }
}
