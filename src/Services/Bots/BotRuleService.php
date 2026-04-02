<?php

namespace Kianisanaullah\TrafficSentinel\Services\Bots;

use Illuminate\Support\Facades\DB;

class BotRuleService
{
    protected function db()
    {
        return \DB::connection(
            config('traffic-sentinel.database.connection', config('database.default'))
        );
    }

    public function blockBot($botName)
    {
        $this->db()->table('traffic_bot_rules')->updateOrInsert(
            ['bot_name' => $botName],
            [
                'action' => 'block',
                'enabled' => true,
                'limit_per_minute' => null,
                'limit_per_hour' => null,
                'limit_per_day' => null
            ]
        );
    }

    public function throttleBot($botName, $rpm = 60)
    {
        $this->db()->table('traffic_bot_rules')->updateOrInsert(
            ['bot_name' => $botName],
            [
                'action' => 'throttle',
                'limit_per_minute' => $rpm,
                'enabled' => true
            ]
        );
    }

    public function monitorBot($botName)
    {
        $this->db()->table('traffic_bot_rules')->updateOrInsert(
            ['bot_name' => $botName],
            [
                'action' => 'monitor',
                'enabled' => true,
                'limit_per_minute' => null
            ]
        );
    }
    public function blockIp(string $ip): void
    {
        $this->db()->table('traffic_bot_rules')->updateOrInsert(
            ['ip' => $ip],
            [
                'action' => 'block',
                'enabled' => true,
                'limit_per_minute' => null,
                'limit_per_hour' => null,
                'limit_per_day' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function throttleIp(string $ip, ?int $perMinute = null, ?int $perHour = null, ?int $perDay = null): void
    {
        $this->db()->table('traffic_bot_rules')->updateOrInsert(
            ['ip' => $ip],
            [
                'action' => 'throttle',
                'enabled' => true,
                'limit_per_minute' => $perMinute,
                'limit_per_hour' => $perHour,
                'limit_per_day' => $perDay,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function monitorIp(string $ip): void
    {
        $this->db()->table('traffic_bot_rules')->updateOrInsert(
            ['ip' => $ip],
            [
                'action' => 'monitor',
                'enabled' => true,
                'limit_per_minute' => null,
                'limit_per_hour' => null,
                'limit_per_day' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
    public function blockMultipleBots(array $bots)
    {
        if (empty($bots)) return;

        $now = now();

        $data = collect($bots)->map(function ($bot) use ($now) {
            return [
                'bot_name' => $bot === 'unknown' ? null : $bot,
                'action' => 'block',
                'enabled' => true,
                'limit_per_minute' => null,
                'limit_per_hour' => null,
                'limit_per_day' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        $this->db()->table('traffic_bot_rules')->upsert(
            $data,
            ['bot_name'],
            ['action', 'enabled', 'limit_per_minute', 'limit_per_hour', 'limit_per_day', 'updated_at']
        );
    }
    public function unblockMultipleBots(array $bots)
    {
        if (empty($bots)) return;

        $now = now();

        $data = collect($bots)->map(function ($bot) use ($now) {
            return [
                'bot_name' => $bot === 'unknown' ? null : $bot,
                'action' => 'monitor',
                'enabled' => true,
                'limit_per_minute' => null,
                'limit_per_hour' => null,
                'limit_per_day' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        $this->db()->table('traffic_bot_rules')->upsert(
            $data,
            ['bot_name'],
            ['action', 'enabled', 'limit_per_minute', 'limit_per_hour', 'limit_per_day', 'updated_at']
        );
    }
}
