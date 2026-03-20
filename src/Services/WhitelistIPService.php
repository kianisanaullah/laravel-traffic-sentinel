<?php
namespace Kianisanaullah\TrafficSentinel\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class WhitelistIPService
{
    public function isWhitelisted(string $ip): bool
    {
        return Cache::remember(
            'ts_whitelist_'.$ip,
            300,
            function () use ($ip) {

                return DB::table('traffic_whitelist')
                    ->where('ip', $ip)
                    ->where('active', true)
                    ->where(function($q){
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at','>',now());
                    })
                    ->exists();

            }
        );
    }
}
