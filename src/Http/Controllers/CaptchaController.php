<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Kianisanaullah\TrafficSentinel\Services\TrafficTracker;
use Kianisanaullah\TrafficSentinel\Services\CacheService;
use Illuminate\Support\Facades\Mail;

class CaptchaController extends Controller
{
    public function show(Request $request)
    {
//        if (!session()->has('traffic_sentinel_intended_url')) {
//            return redirect('/');
//        }

        return view('traffic-sentinel::captcha.challenge', [
            'turnstileSiteKey' => config('traffic-sentinel.captcha.turnstile.site_key'),
            'redirectTo' => session('traffic_sentinel_intended_url', url('/')),
        ]);
    }


    public function verify(Request $request, TrafficTracker $tracker, CacheService $cache)
    {
        $request->validate([
            'cf-turnstile-response' => ['required', 'string'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        $secretKey = config('traffic-sentinel.captcha.turnstile.secret_key');

        if (!$secretKey) {
            return back()->withErrors([
                'captcha' => 'CAPTCHA secret key is not configured.',
            ]);
        }

        $response = Http::asForm()->post(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            [
                'secret' => $secretKey,
                'response' => $request->input('cf-turnstile-response'),
                'remoteip' => $request->ip(),
            ]
        );

        $result = $response->json();

        $ipStored = $tracker->ipForStorage($request->ip());

        // ❌ CAPTCHA FAILED
        if (!($result['success'] ?? false)) {

            if ($ipStored) {

                $failKey = 'captcha_fail:' . $ipStored;

                // ✅ SAFE increment (TTL handled)
                $fails = $cache->increment($failKey, 30);

                $limit = (int) config('traffic-sentinel.captcha.fail_limit', 3);

                if ($fails >= $limit) {

                    $blockKey = 'ip_blocked:' . $ipStored;

                    $cache->setFlag(
                        $blockKey,
                        (int) config('traffic-sentinel.captcha.block_hours', 24) * 60
                    );

                    $cache->reset($failKey);

                    $this->sendCaptchaBlockAlert($ipStored);

                    abort(403, 'Your IP has been blocked due to repeated CAPTCHA failures.');
                }
            }

            return back()->withErrors([
                'captcha' => 'CAPTCHA verification failed. Please try again.',
            ]);
        }

        // ✅ CAPTCHA SUCCESS
        if ($ipStored) {

            $cache->setFlag(
                'captcha_passed:' . $ipStored,
                (int) config('traffic-sentinel.captcha.pass_minutes', 30)
            );

            $cache->reset('ts_captcha_required:' . $ipStored);
            $cache->reset('ts_captcha_fail:' . $ipStored);
        }

        $redirectTo = $request->input('redirect_to')
            ?: session('traffic_sentinel_intended_url', url('/'));

        session()->forget('traffic_sentinel_intended_url');

        return redirect($redirectTo);
    }
    private function sendCaptchaBlockAlert(string $ip)
    {
        try {

            $emails = config('traffic-sentinel.alerts.email', []);

            /*
            |--------------------------------------------------------------------------
            | 🔥 Normalize Emails (JSON + comma + string safe)
            |--------------------------------------------------------------------------
            */
            if (is_string($emails)) {

                $decoded = json_decode($emails, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $emails = $decoded;
                } else {
                    $emails = array_map('trim', explode(',', $emails));
                }
            }

            $emails = collect($emails)
                ->filter()
                ->values()
                ->all();

            if (empty($emails)) {
                return;
            }

            \Mail::to($emails)
                ->send(new \Kianisanaullah\TrafficSentinel\Mail\CaptchaBlockedMail([
                    'ip'        => $ip,
                    'host'      => request()->getHost(),
                    'userAgent' => request()->userAgent(),
                    'reason'    => 'Repeated CAPTCHA failures',
                    'status'    => 'BLOCKED',
                    'time'      => now(),
                ]));

        } catch (\Throwable $e) {

            \Log::channel('traffic_sentinel')->error('Captcha block email failed', [
                'ip'    => $ip,
                'error' => $e->getMessage()
            ]);
        }
    }
}
