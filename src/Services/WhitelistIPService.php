<?php
namespace Kianisanaullah\TrafficSentinel\Services;

use Illuminate\Support\Facades\DB;
use Kianisanaullah\TrafficSentinel\Services\CacheService;

class WhitelistIPService
{
    protected $cache;

    public function __construct(CacheService $cache)
    {
        $this->cache = $cache;
    }
    public function isWhitelisted(string $ip): bool
    {
        return $this->cache->remember(
            "whitelist:$ip",
            5, // minutes (300 sec)
            function () use ($ip) {

                return DB::table('traffic_whitelist')
                    ->where('ip', $ip)
                    ->where('active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->exists();
            }
        );
    }
}
