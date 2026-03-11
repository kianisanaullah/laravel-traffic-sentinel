<?php
class TrafficBotSync extends Command
{
    protected $signature = 'traffic:bot-sync';

    public function handle()
    {
        $bots = DB::table('traffic_sessions_bots')
            ->select('bot_name')
            ->distinct()
            ->whereNotNull('bot_name')
            ->pluck('bot_name');

        foreach ($bots as $bot) {

            DB::table('traffic_bot_rules')->updateOrInsert(
                ['bot_name'=>$bot],
                [
                    'limit_per_minute'=>60,
                    'limit_per_hour'=>2000,
                    'action'=>'monitor',
                    'created_at'=>now(),
                    'updated_at'=>now()
                ]
            );

        }

        $this->info('Bot rules synchronized.');
    }
}
