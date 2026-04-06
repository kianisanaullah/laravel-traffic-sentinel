<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Kianisanaullah\TrafficSentinel\Models\TrafficSetting;

class SettingsController extends Controller
{
    public function index()
    {
        $settingsMap = TrafficSetting::pluck('value', 'key')->toArray();

        return view('traffic-sentinel::settings.index', [
            'config' => config('traffic-sentinel'),
            'schema' => config('traffic-sentinel-settings-schema'),
            'settingsMap' => $settingsMap,
        ]);
    }

    public function save(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {

            // Handle JSON fields
            if (is_string($value) && $this->isJson($value)) {
                $value = json_decode($value, true);
            }

            TrafficSetting::updateOrCreate(
                ['key' => $key],
                ['value' => json_encode($value)]
            );
        }

        cache()->forget('ts_settings_all');

        return back()->with('success', 'Settings saved successfully 🚀');
    }

    private function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
