<?php

namespace Kianisanaullah\TrafficSentinel\Services\Bots;

use Illuminate\Support\Facades\DB;

class BotProtectionService
{
    protected function db()
    {
        return \DB::connection(
            config('traffic-sentinel.database.connection', config('database.default'))
        );
    }
    public function check($botName = null, $ip = null, $host = null, $app = null)
    {
        $rule = $this->getIpRule($ip);
        if ($rule) {
            return $rule;
        }

        $rule = $this->getBotRule($botName);
        if ($rule) {
            return $rule;
        }

        $rule = $this->getHostRule($host);
        if ($rule) {
            return $rule;
        }

        $rule = $this->getAppRule($app);
        if ($rule) {
            return $rule;
        }

        return null;
    }

    public function getIpRule($ip)
    {
        if (!$ip) {
            return null;
        }

        return $this->db()->table('traffic_bot_rules')
            ->where('enabled', true)
            ->where('ip', $ip)
            ->first();
    }

    public function getBotRule($botName)
    {
        if (!$botName) {
            return null;
        }

        return $this->db()->table('traffic_bot_rules')
            ->where('enabled', true)
            ->where('bot_name', $botName)
            ->first();
    }

    public function getHostRule($host)
    {
        if (!$host) {
            return null;
        }

        return $this->db()->table('traffic_bot_rules')
            ->where('enabled', true)
            ->where('host', $host)
            ->first();
    }

    public function getAppRule($app)
    {
        if (!$app) {
            return null;
        }

        return $this->db()->table('traffic_bot_rules')
            ->where('enabled', true)
            ->where('app_key', $app)
            ->first();
    }

    public function getLimitPerMinute($rule)
    {
        return $rule->limit_per_minute ?? null;
    }

    public function getLimitPerHour($rule)
    {
        return $rule->limit_per_hour ?? null;
    }

    public function getLimitPerDay($rule)
    {
        return $rule->limit_per_day ?? null;
    }
}
