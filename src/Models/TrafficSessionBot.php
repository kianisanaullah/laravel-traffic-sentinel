<?php

namespace Kianisanaullah\TrafficSentinel\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficSessionBot extends Model
{
    protected $table = 'traffic_sessions_bots';

    protected $fillable = [
        'app_key','session_id','visitor_key','host','bot_name','ip','ip_raw','user_agent','device_type',
        'referrer','landing_url','first_seen_at','last_seen_at','user_id',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
    ];

    public function pageviews()
    {
        return $this->hasMany(TrafficPageviewBot::class, 'traffic_session_id');
    }
}
