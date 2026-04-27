<?php

namespace Kianisanaullah\TrafficSentinel\Database\Seeders;

use Illuminate\Database\Seeder;
use Kianisanaullah\TrafficSentinel\Models\TrafficSetting;

class TrafficSettingsSeeder extends Seeder
{
    public function run()
    {
        $appKey = config('traffic-sentinel.tracking.app_key');
        $config = config('traffic-sentinel');
        $schema = config('traffic-sentinel-settings-schema');

        foreach ($schema as $group => $fields) {
            foreach ($fields as $key => $meta) {

                // remove prefix
                $configKey = str_replace('traffic-sentinel.', '', $key);

                $value = data_get($config, $configKey);

                // skip if null
                if ($value === null) {
                    continue;
                }

                TrafficSetting::updateOrCreate(
                    [
                        'app_key' => $appKey,
                        'key' => $key,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        }
    }
}
