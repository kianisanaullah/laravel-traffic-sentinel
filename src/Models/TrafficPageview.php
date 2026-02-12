<?php

namespace Kianisanaullah\TrafficSentinel\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficPageview extends Model
{

    protected $table = 'traffic_pageviews';
    protected $connection;
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->connection = config('traffic-sentinel.database.connection', 'mysql');
    }

    protected $guarded = [];

    protected $casts = [
        'is_bot' => 'boolean',
        'viewed_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(TrafficSession::class, 'traffic_session_id');
    }
}
