<?php

namespace Kianisanaullah\TrafficSentinel\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficPageviewBot extends Model
{
    protected $table = 'traffic_pageviews_bots';

    protected $fillable = [
        'app_key','traffic_session_id','host','bot_name','method','path','full_url','route_name',
        'status_code','duration_ms','viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(TrafficSessionBot::class, 'traffic_session_id');
    }
}
