<?php

namespace Kianisanaullah\TrafficSentinel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillSplitTables extends Command
{
    protected $signature = 'traffic-sentinel:backfill-split
        {--from= : Start datetime (Y-m-d H:i:s). Default: null (all)}
        {--to= : End datetime (Y-m-d H:i:s). Default: now()}
        {--chunk=2000 : Chunk size for sessions/pageviews}
        {--only=sessions : sessions|pageviews|all}
        {--conn= : DB connection override}';

    protected $description = 'Backfill old traffic_sessions/traffic_pageviews into split humans/bots tables with mapping.';

    public function handle(): int
    {
        $conn = $this->option('conn') ?: config('traffic-sentinel.database.connection', config('database.default'));
        DB::connection($conn)->disableQueryLog();

        $from = $this->option('from') ? Carbon::parse($this->option('from')) : null;
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : now();

        $only = strtolower((string)$this->option('only'));
        if (!in_array($only, ['sessions', 'pageviews', 'all'], true)) $only = 'all';

        if ($only === 'sessions' || $only === 'all') {
            $this->backfillSessions($conn, $from, $to);
        }

        if ($only === 'pageviews' || $only === 'all') {
            $this->backfillPageviews($conn, $from, $to);
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    protected function backfillSessions(string $conn, ?Carbon $from, Carbon $to): void
    {
        $chunk = (int)$this->option('chunk');

        $q = DB::connection($conn)->table('traffic_sessions')
            ->orderBy('id');

        if ($from) $q->where('first_seen_at', '>=', $from);
        $q->where('first_seen_at', '<=', $to);

        $this->info('Backfilling sessions...');
        $bar = $this->output->createProgressBar((int)$q->count());
        $bar->start();

        $q->chunkById($chunk, function ($rows) use ($conn, $bar) {
            $humans = [];
            $bots = [];
            $humanOldIds = [];
            $botOldIds = [];

            foreach ($rows as $r) {
                $base = [
                    'app_key' => $r->app_key,
                    'session_id' => $r->session_id,
                    'visitor_key' => $r->visitor_key,
                    'host' => $r->host,
                    'ip' => $r->ip,
                    'ip_raw' => $r->ip_raw,
                    'user_agent' => $r->user_agent,
                    'device_type' => $r->device_type,
                    'referrer' => $r->referrer,
                    'landing_url' => $r->landing_url,
                    'first_seen_at' => $r->first_seen_at,
                    'last_seen_at' => $r->last_seen_at,
                    'user_id' => $r->user_id,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ];

                if ((int)$r->is_bot === 1) {
                    $base['bot_name'] = $r->bot_name;
                    $bots[] = $base;
                    $botOldIds[] = (int)$r->id;
                } else {
                    $humans[] = $base;
                    $humanOldIds[] = (int)$r->id;
                }
            }

            DB::connection($conn)->transaction(function () use ($conn, $humans, $bots, $humanOldIds, $botOldIds) {
                if (!empty($humans)) {
                    DB::connection($conn)->table('traffic_sessions_humans')->insertOrIgnore($humans);

                    $map = DB::connection($conn)->table('traffic_sessions_humans')
                        ->whereIn('session_id', array_column($humans, 'session_id'))
                        ->select('id', 'session_id')
                        ->get()
                        ->keyBy('session_id');

                    $mapRows = [];
                    foreach ($humans as $i => $h) {
                        $oldId = $humanOldIds[$i];
                        $newId = (int)($map[$h['session_id']]->id ?? 0);
                        if ($newId > 0) {
                            $mapRows[] = ['old_id' => $oldId, 'new_id' => $newId];
                        }
                    }
                    if ($mapRows) {
                        DB::connection($conn)->table('ts_map_h')->insertOrIgnore($mapRows);
                    }
                }

                if (!empty($bots)) {
                    DB::connection($conn)->table('traffic_sessions_bots')->insertOrIgnore($bots);

                    $map = DB::connection($conn)->table('traffic_sessions_bots')
                        ->whereIn('session_id', array_column($bots, 'session_id'))
                        ->select('id', 'session_id')
                        ->get()
                        ->keyBy('session_id');

                    $mapRows = [];
                    foreach ($bots as $i => $b) {
                        $oldId = $botOldIds[$i];
                        $newId = (int)($map[$b['session_id']]->id ?? 0);
                        if ($newId > 0) {
                            $mapRows[] = ['old_id' => $oldId, 'new_id' => $newId];
                        }
                    }
                    if ($mapRows) {
                        DB::connection($conn)->table('ts_map_b')->insertOrIgnore($mapRows);
                    }
                }
            });

            $bar->advance(count($rows));
        });

        $bar->finish();
        $this->newLine();
    }

    protected function backfillPageviews(string $conn, ?Carbon $from, Carbon $to): void
    {
        $chunk = (int)$this->option('chunk');

        $q = DB::connection($conn)->table('traffic_pageviews')->orderBy('id');

        if ($from) $q->where('viewed_at', '>=', $from);
        $q->where('viewed_at', '<=', $to);

        $this->info('Backfilling pageviews...');
        $bar = $this->output->createProgressBar((int)$q->count());
        $bar->start();

        $q->chunkById($chunk, function ($rows) use ($conn, $bar) {
            $humans = [];
            $bots = [];

            $humanSessOldIds = [];
            $botSessOldIds = [];
            $allSessOldIds = [];

            foreach ($rows as $r) {
                $sid = (int)$r->traffic_session_id;
                $allSessOldIds[] = $sid;

                if ((int)$r->is_bot === 1) {
                    $botSessOldIds[] = $sid;
                } else {
                    $humanSessOldIds[] = $sid;
                }
            }

            $humanSessOldIds = array_values(array_unique($humanSessOldIds));
            $botSessOldIds = array_values(array_unique($botSessOldIds));
            $allSessOldIds = array_values(array_unique($allSessOldIds));

            // ✅ Pull IP/IP_RAW from OLD sessions in bulk (fast)
            $sessIpMap = [];
            if ($allSessOldIds) {
                $sessIpMap = DB::connection($conn)->table('traffic_sessions')
                    ->whereIn('id', $allSessOldIds)
                    ->select('id', 'ip', 'ip_raw', 'referrer')
                    ->get()
                    ->keyBy('id')
                    ->all();
            }

            // ✅ Mapping old session id -> new session id for humans/bots
            $humanMap = [];
            if ($humanSessOldIds) {
                $humanMap = DB::connection($conn)->table('ts_map_h')
                    ->whereIn('old_id', $humanSessOldIds)
                    ->pluck('new_id', 'old_id')
                    ->all();
            }

            $botMap = [];
            if ($botSessOldIds) {
                $botMap = DB::connection($conn)->table('ts_map_b')
                    ->whereIn('old_id', $botSessOldIds)
                    ->pluck('new_id', 'old_id')
                    ->all();
            }

            foreach ($rows as $r) {
                $oldSessId = (int)$r->traffic_session_id;

                $ipRow = $sessIpMap[$oldSessId] ?? null;

                $ip = $ipRow ? ($ipRow->ip ?? null) : null;
                $ipRaw = $ipRow ? ($ipRow->ip_raw ?? null) : null;
                $referrer = $ipRow ? ($ipRow->referrer ?? null) : null;

                $base = [
                    'app_key' => $r->app_key,
                    'host' => $r->host,
                    'method' => $r->method,
                    'path' => $r->path,
                    'full_url' => $r->full_url,
                    'route_name' => $r->route_name,
                    'status_code' => $r->status_code,
                    'duration_ms' => $r->duration_ms,
                    'viewed_at' => $r->viewed_at,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,

                    'referrer' => $referrer,
                    'ip' => $ip,
                    'ip_raw' => $ipRaw,
                ];

                if ((int)$r->is_bot === 1) {
                    $newSessId = (int)($botMap[$oldSessId] ?? 0);
                    if ($newSessId <= 0) continue;

                    $base['traffic_session_id'] = $newSessId;
                    $base['bot_name'] = $r->bot_name;
                    $bots[] = $base;
                } else {
                    $newSessId = (int)($humanMap[$oldSessId] ?? 0);
                    if ($newSessId <= 0) continue;

                    $base['traffic_session_id'] = $newSessId;
                    $humans[] = $base;
                }
            }

            DB::connection($conn)->transaction(function () use ($conn, $humans, $bots) {
                if ($humans) DB::connection($conn)->table('traffic_pageviews_humans')->insertOrIgnore($humans);
                if ($bots) DB::connection($conn)->table('traffic_pageviews_bots')->insertOrIgnore($bots);
            });

            $bar->advance(count($rows));
        });

        $bar->finish();
        $this->newLine();
    }
}
