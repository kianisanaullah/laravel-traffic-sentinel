<?php

namespace Kianisanaullah\TrafficSentinel\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficSession extends Model
{
    protected $table = 'traffic_sessions';

    protected $guarded = [];

    protected $casts = [
        'is_bot' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function pageviews()
    {
        return $this->hasMany(TrafficPageview::class, 'traffic_session_id');
    }
}
