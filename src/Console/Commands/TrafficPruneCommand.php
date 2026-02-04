<?php

namespace Kianisanaullah\TrafficSentinel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageview;
use Kianisanaullah\TrafficSentinel\Models\TrafficSession;

class TrafficPruneCommand extends Command
{
    protected $signature = 'traffic:prune
        {--days=30 : Delete sessions/pageviews older than this many days}
        {--pageviews-only=0 : If 1, delete only pageviews older than days, keep sessions}';

    protected $description = 'Prune old Traffic Sentinel records';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $pageviewsOnly = (int) $this->option('pageviews-only') === 1;

        $cutoff = Carbon::now()->subDays($days);

        $this->info("Pruning records older than {$cutoff} (days={$days}) ...");

        $pv = TrafficPageview::query()->where('viewed_at', '<', $cutoff)->delete();
        $this->line("Deleted pageviews: {$pv}");

        if (! $pageviewsOnly) {
            $ss = TrafficSession::query()->where('last_seen_at', '<', $cutoff)->delete();
            $this->line("Deleted sessions: {$ss}");
        }

        $this->info("Done.");
        return self::SUCCESS;
    }
}
