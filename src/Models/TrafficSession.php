<?php

namespace Kianisanaullah\TrafficSentinel\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficSession extends Model
{
    protected $table = 'traffic_sessions';
    protected $guarded = [];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'is_bot' => 'boolean',
    ];

    public function pageviews()
    {
        return $this->hasMany(TrafficPageview::class, 'traffic_session_id');
    }
}
