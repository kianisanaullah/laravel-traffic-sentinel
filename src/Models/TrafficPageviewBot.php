<?php

namespace Kianisanaullah\TrafficSentinel\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficPageviewBot extends Model
{
    protected $table = 'traffic_pageviews_bots';
    protected $connection;
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->connection = config('traffic-sentinel.database.connection', 'mysql');
    }
    protected $guarded = [];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(TrafficSessionBot::class, 'traffic_session_id');
    }
}
