<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Kianisanaullah\TrafficSentinel\Services\TrafficTracker;
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


    public function verify(Request $request, TrafficTracker $tracker)
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

        // CAPTCHA FAILED
        if (!($result['success'] ?? false)) {

            if ($ipStored) {

                $failKey = 'ts_captcha_fail:' . $ipStored;

                $fails = Cache::increment($failKey);

                if ($fails == 1) {
                    Cache::put($failKey, 1, now()->addMinutes(30));
                }

                $limit = (int) config('traffic-sentinel.captcha.fail_limit', 3);

                if ($fails >= $limit) {

                    $blockKey = 'ts_ip_blocked:' . $ipStored;

                    Cache::put(
                        $blockKey,
                        true,
                        now()->addHours(
                            (int) config('traffic-sentinel.captcha.block_hours', 24)
                        )
                    );

                    Cache::forget($failKey);

                    $this->sendCaptchaBlockAlert($ipStored);

                    abort(403, 'Your IP has been blocked due to repeated CAPTCHA failures.');
                }
            }

            return back()->withErrors([
                'captcha' => 'CAPTCHA verification failed. Please try again.',
            ]);
        }

        // CAPTCHA SUCCESS
        if ($ipStored) {

            Cache::put(
                'ts_captcha_passed:' . $ipStored,
                true,
                now()->addMinutes(
                    (int) config('traffic-sentinel.captcha.pass_minutes', 30)
                )
            );

            Cache::forget('ts_captcha_required:' . $ipStored);
            Cache::forget('ts_captcha_fail:' . $ipStored);
        }

        $redirectTo = $request->input('redirect_to')
            ?: session('traffic_sentinel_intended_url', url('/'));

        session()->forget('traffic_sentinel_intended_url');

        return redirect($redirectTo);
    }

    private function sendCaptchaBlockAlert(string $ip)
    {
        $emails = collect(explode(',', (string)config('traffic-sentinel.alerts.email')))
            ->map(fn($e) => trim($e))
            ->filter()
            ->values()
            ->all();

        if (empty($emails)) {
            return;
        }

        try {

            Mail::send(
                'traffic-sentinel::emails.captcha-blocked',
                [
                    'ip' => $ip,
                    'host' => request()->getHost(),
                    'userAgent' => request()->userAgent(),
                    'reason' => 'Repeated CAPTCHA failures',
                    'status' => 'BLOCKED',
                    'time' => now(),
                ],
                function ($msg) use ($emails, $ip) {

                    $msg->to($emails)
                        ->subject('🚫 Traffic Sentinel — IP Blocked after CAPTCHA Failures: ' . $ip);

                }
            );

        } catch (\Throwable $e) {

            \Log::error('TrafficSentinel captcha block email failed', [
                'error' => $e->getMessage()
            ]);

        }
    }
}
