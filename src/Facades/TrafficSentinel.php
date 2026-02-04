<?php

namespace Kianisanaullah\TrafficSentinel\Facades;

use Illuminate\Support\Facades\Facade;
use Kianisanaullah\TrafficSentinel\Services\TrafficStats;

class TrafficSentinel extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TrafficStats::class;
    }
}
