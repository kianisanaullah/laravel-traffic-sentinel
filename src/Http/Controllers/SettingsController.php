<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kianisanaullah\TrafficSentinel\Models\TrafficSetting;

class SettingsController extends Controller
{
    public function index()
    {
        $appKey = config('traffic-sentinel.tracking.app_key');

        $settingsMap = TrafficSetting::where('app_key', $appKey)
            ->get()
            ->mapWithKeys(function ($item) {

                $value = $item->value;

                // 🔥 normalize values
                if ($value === '1' || $value === 1) $value = 1;
                elseif ($value === '0' || $value === 0) $value = 0;
                elseif (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value = $decoded;
                    }
                }

                return [$item->key => $value];
            })
            ->toArray();

        return view('traffic-sentinel::settings.index', [
            'config' => config('traffic-sentinel'),
            'schema' => config('traffic-sentinel-settings-schema'),
            'settingsMap' => $settingsMap,
        ]);
    }

    public function save(Request $request)
    {
        $appKey = config('traffic-sentinel.tracking.app_key');
        $schema = config('traffic-sentinel-settings-schema');

        foreach ($schema as $group => $fields) {
            foreach ($fields as $key => $meta) {

                // 🔥 convert key → input name
                $inputName = str_replace(['.', '-'], ['__', '_'], $key);

                $value = $request->input($inputName);

                /*
                |--------------------------------------------------------------------------
                | BOOLEAN FIX
                |--------------------------------------------------------------------------
                */
                if ($meta['type'] === 'boolean') {
                    $value = ($value == 1) ? 1 : 0;
                }

                /*
                |--------------------------------------------------------------------------
                | JSON FIX
                |--------------------------------------------------------------------------
                */
                if ($meta['type'] === 'json') {
                    if (!empty($value)) {

                        $decoded = json_decode($value, true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            continue;
                        }

                        $value = json_encode($decoded);
                    } else {
                        $value = json_encode([]);
                    }
                }

                if (is_array($value)) {
                    $value = json_encode($value);
                }

                if ($value === null) continue;

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

        cache()->forget("ts_settings_{$appKey}");

        return back()->with('success', 'Settings saved successfully 🚀');
    }
}
