<?php

namespace Kianisanaullah\TrafficSentinel\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Kianisanaullah\TrafficSentinel\Models\TrafficSessionHuman;
use Kianisanaullah\TrafficSentinel\Models\TrafficSessionBot;

class FilterService
{
    public function resolve(Request $request): array
    {
        $range = $request->get('range', 'today');

        if (!in_array($range, ['today', '7', '30'])) {
            $range = 'today';
        }

        $days = $range === '7' ? 7 : ($range === '30' ? 30 : 1);

        $end   = Carbon::now();
        $start = $days === 1
            ? Carbon::today()
            : Carbon::now()->subDays($days)->startOfDay();

        $host = trim((string)$request->get('host', '')) ?: null;
        $app  = trim((string)$request->get('app', '')) ?: null;

        return [
            'range' => $range,
            'days'  => $days,
            'start' => $start,
            'end'   => $end,
            'host'  => $host,
            'app'   => $app,
        ];
    }

    public function dropdownLists(): array
    {
        $hosts = TrafficSessionHuman::query()
            ->select('host')
            ->whereNotNull('host')
            ->distinct()
            ->pluck('host')
            ->merge(
                TrafficSessionBot::query()
                    ->select('host')
                    ->whereNotNull('host')
                    ->distinct()
                    ->pluck('host')
            )
            ->unique()
            ->sort()
            ->values()
            ->all();

        $apps = TrafficSessionHuman::query()
            ->select('app_key')
            ->whereNotNull('app_key')
            ->distinct()
            ->pluck('app_key')
            ->merge(
                TrafficSessionBot::query()
                    ->select('app_key')
                    ->whereNotNull('app_key')
                    ->distinct()
                    ->pluck('app_key')
            )
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [$hosts, $apps];
    }
}
