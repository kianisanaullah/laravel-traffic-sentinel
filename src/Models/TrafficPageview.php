<?php

namespace Kianisanaullah\TrafficSentinel\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficPageview extends Model
{
    protected $table = 'traffic_pageviews';
    protected $guarded = [];

    protected $casts = [
        'viewed_at' => 'datetime',
        'is_bot' => 'boolean',
    ];

    public function session()
    {
        return $this->belongsTo(TrafficSession::class, 'traffic_session_id');
    }
}
