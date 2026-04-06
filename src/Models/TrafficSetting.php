<?php

namespace Kianisanaullah\TrafficSentinel\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficSetting extends Model
{
    protected $table = 'traffic_settings';

    protected $guarded = [];

    protected $connection;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->connection = config('traffic-sentinel.database.connection', 'mysql');
    }

    protected $casts = [
        'value' => 'array',
    ];
}
