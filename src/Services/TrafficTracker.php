<?php

namespace Kianisanaullah\TrafficSentinel\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageview;
use Kianisanaullah\TrafficSentinel\Models\TrafficSession;

class TrafficTracker
{
    public function track(Request $request, int $durationMs, ?int $statusCode, ?string $visitorId = null): void
    {
        if (! $this->shouldTrackBasic($request)) return;

        $now = Carbon::now();

        $sessionId = $this->getSessionId($request);
        $ua = (string) $request->userAgent();
        $ipStored = $this->ipToStore($request->ip());

        $visitorKey = $this->makeVisitorKey($ipStored, $ua, $visitorId);

        $host = strtolower((string) $request->getHost());

        [$isBot, $botName] = $this->detectBot($request);

        // If bot and tracking bots disabled => skip
        if ($isBot && ! config('traffic-sentinel.bots.track_bots', true)) {
            return;
        }

        $deviceType = $isBot ? 'bot' : $this->detectDeviceType($ua);

        $ref = (string) $request->headers->get('referer');
        $fullUrl = (string) $request->fullUrl();
        $path = '/' . ltrim((string) $request->path(), '/');
        $routeName = optional($request->route())->getName();

        $userId = auth()->check() ? (int) auth()->id() : null;

        DB::transaction(function () use (
            $sessionId,
            $visitorKey,
            $ipStored,
            $ua,
            $deviceType,
            $ref,
            $fullUrl,
            $path,
            $routeName,
            $now,
            $userId,
            $durationMs,
            $statusCode,
            $request,
            $isBot,
            $botName,
            $host
        ) {
            /** @var TrafficSession|null $session */
            $session = TrafficSession::query()
                ->where('session_id', $sessionId)
                ->first();

            if (! $session) {
                $session = TrafficSession::create([
                    'session_id'    => $sessionId,
                    'visitor_key'   => $visitorKey,
                    'host'          => $host,
                    'ip'            => $ipStored,
                    'user_agent'    => Str::limit($ua, 500, ''),
                    'device_type'   => $deviceType,
                    'referrer'      => Str::limit($ref ?: null, 500, ''),
                    'landing_url'   => Str::limit($fullUrl ?: null, 500, ''),
                    'first_seen_at' => $now,
                    'last_seen_at'  => $now,
                    'user_id'       => $userId,
                    'is_bot'        => $isBot,
                    'bot_name'      => $botName,
                ]);
            } else {
                $session->update([
                    'last_seen_at' => $now,
                    'user_id'      => $userId ?? $session->user_id,
                    'is_bot'       => $session->is_bot || $isBot,
                    'bot_name'     => $session->bot_name ?: $botName,


                    'host'         => $session->host ?: $host,
                ]);
            }

            TrafficPageview::create([
                'traffic_session_id' => $session->id,
                'host'               => $host,
                'method'             => $request->method(),
                'path'               => Str::limit($path, 500, ''),
                'full_url'           => Str::limit($fullUrl, 800, ''),
                'route_name'          => $routeName,
                'status_code'         => $statusCode ? (string) $statusCode : null,
                'duration_ms'         => $durationMs,
                'viewed_at'           => $now,
                'is_bot'              => $isBot,
                'bot_name'            => $botName,
            ]);
        });
    }

    protected function shouldTrackBasic(Request $request): bool
    {
        if (! in_array($request->method(), config('traffic-sentinel.methods', ['GET']), true)) {
            return false;
        }

        if (config('traffic-sentinel.ignore_authenticated', false) && auth()->check()) {
            return false;
        }

        $path = ltrim((string) $request->path(), '/');

        foreach (config('traffic-sentinel.ignore_prefixes', []) as $prefix) {
            $prefix = trim($prefix, '/');
            if ($prefix !== '' && Str::startsWith($path, $prefix)) {
                return false;
            }
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext && in_array($ext, config('traffic-sentinel.ignore_extensions', []), true)) {
            return false;
        }

        return true;
    }

    /**
     * Returns [isBot, botName]
     */
    protected function detectBot(Request $request): array
    {
        if (! config('traffic-sentinel.bots.enabled', true)) {
            return [false, null];
        }

        $ua = strtolower((string) $request->userAgent());
        if ($ua === '') return [true, 'unknown'];

        foreach ((array) config('traffic-sentinel.bots.ua_keywords', []) as $kw) {
            $kw = strtolower((string) $kw);
            if ($kw !== '' && str_contains($ua, $kw)) {
                // Some helpful naming
                if (str_contains($ua, 'googlebot')) return [true, 'googlebot'];
                if (str_contains($ua, 'bingbot')) return [true, 'bingbot'];
                if (str_contains($ua, 'facebookexternalhit')) return [true, 'facebook'];
                if (str_contains($ua, 'slurp')) return [true, 'yahoo'];
                if (str_contains($ua, 'duckduckbot')) return [true, 'duckduckgo'];
                if (str_contains($ua, 'ahrefs')) return [true, 'ahrefs'];
                if (str_contains($ua, 'semrush')) return [true, 'semrush'];

                return [true, $kw];
            }
        }

        // Heuristic: headless + missing Accept-Language often automation
        $acceptLang = (string) $request->headers->get('accept-language');
        if ($acceptLang === '' && str_contains($ua, 'headless')) return [true, 'headless'];

        return [false, null];
    }

    protected function detectDeviceType(string $ua): string
    {
        $u = strtolower($ua);
        if ($u === '') return 'unknown';
        if (str_contains($u, 'mobile') || str_contains($u, 'android') || str_contains($u, 'iphone')) return 'mobile';
        return 'desktop';
    }

    protected function getSessionId(Request $request): string
    {
        try {
            if ($request->hasSession()) {
                return (string) $request->session()->getId();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // for stateless routes (rare in "web"), still track
        return 'stateless_' . (string) Str::uuid();
    }

    protected function ipToStore(?string $ip): ?string
    {
        if (! $ip) return null;

        $mode = config('traffic-sentinel.ip.store', 'hashed');
        if ($mode === 'full') return $ip;

        $salt = (string) config('traffic-sentinel.ip.hash_salt', '');
        return hash('sha256', $salt . '|' . $ip);
    }

    protected function makeVisitorKey(?string $ipStored, string $ua, ?string $visitorId = null): string
    {
        $visitorId = trim((string) $visitorId);
        if ($visitorId !== '') {
            return 'vid_' . substr(hash('sha256', strtolower($visitorId)), 0, 32);
        }
        
        $uaShort = substr(hash('sha1', strtolower($ua)), 0, 16);
        $ipPart  = $ipStored ? substr(hash('sha1', $ipStored), 0, 16) : 'noip';

        return $ipPart . '-' . $uaShort;
    }
}
