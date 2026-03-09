<?php

namespace Kianisanaullah\TrafficSentinel\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficPageviewHuman extends Model
{
    protected $table = 'traffic_pageviews_humans';
    protected $connection;
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->connection = config('traffic-sentinel.database.connection', 'mysql');
    }
    protected $fillable = [
        'app_key','traffic_session_id','host','method','path','full_url','route_name',
        'status_code','duration_ms','viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(TrafficSessionHuman::class, 'traffic_session_id');
    }
}
