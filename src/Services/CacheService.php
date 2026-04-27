<?php

namespace Kianisanaullah\TrafficSentinel\Services;

use Illuminate\Support\Facades\Cache;
use Throwable;

class CacheService
{
    protected $store;
    protected $prefix;
    protected $ttl;

    public function __construct()
    {
        $config = config('traffic-sentinel.cache');

        $this->store  = $config['store'] ?? 'traffic_sentinel_db';
        $this->prefix = $config['prefix'] ?? 'ts:';
        $this->ttl    = $config['ttl_minutes'] ?? 10;
    }

    protected function cache()
    {
        return Cache::store($this->store);
    }

    protected function key($key)
    {
        return $this->prefix . $key;
    }

    /*
    |--------------------------------------------------------------------------
    | Basic Methods
    |--------------------------------------------------------------------------
    */

    public function get($key, $default = null)
    {
        try {
            return $this->cache()->get($this->key($key), $default);
        } catch (Throwable $e) {
            return $default;
        }
    }

    public function put($key, $value, $ttlMinutes = null)
    {
        $ttl = $ttlMinutes ?? $this->ttl;

        try {
            return $this->cache()->put(
                $this->key($key),
                $value,
                now()->addMinutes($ttl)
            );
        } catch (Throwable $e) {
            return false;
        }
    }

    public function forget($key)
    {
        try {
            return $this->cache()->forget($this->key($key));
        } catch (Throwable $e) {
            return false;
        }
    }

    public function has($key)
    {
        try {
            return $this->cache()->has($this->key($key));
        } catch (Throwable $e) {
            return false;
        }
    }

    public function flush()
    {
        try {
            return $this->cache()->flush();
        } catch (Throwable $e) {
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Remember (🔥 REQUIRED)
    |--------------------------------------------------------------------------
    */

    public function remember($key, $ttl, \Closure $callback)
    {
        try {
            if (is_int($ttl)) {
                // treat as seconds
                $ttl = now()->addSeconds($ttl);
            }

            return $this->cache()->remember(
                $this->key($key),
                $ttl,
                $callback
            );
        } catch (\Throwable $e) {
            return $callback();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Smart Increment (TTL Safe 🔥)
    |--------------------------------------------------------------------------
    */

    public function increment($key, $ttlMinutes = null, $step = 1)
    {
        $ttl = $ttlMinutes ?? $this->ttl;

        try {
            $current = (int) $this->get($key, 0) + $step;

            $this->put($key, $current, $ttl);

            return $current;
        } catch (Throwable $e) {
            return 1;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Helpers
    |--------------------------------------------------------------------------
    */

    public function hit($key, $ttlMinutes = null)
    {
        return $this->increment($key, $ttlMinutes);
    }

    public function tooManyAttempts($key, $maxAttempts)
    {
        return (int) $this->get($key, 0) >= $maxAttempts;
    }

    public function reset($key)
    {
        return $this->forget($key);
    }

    /*
    |--------------------------------------------------------------------------
    | Boolean Flags
    |--------------------------------------------------------------------------
    */

    public function setFlag($key, $ttlMinutes)
    {
        return $this->put($key, true, $ttlMinutes);
    }

    public function hasFlag($key)
    {
        return $this->get($key, false) === true;
    }

    /*
    |--------------------------------------------------------------------------
    | Advanced (Optional but Powerful)
    |--------------------------------------------------------------------------
    */

    public function forever($key, $value)
    {
        try {
            return $this->cache()->forever($this->key($key), $value);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function pull($key, $default = null)
    {
        try {
            return $this->cache()->pull($this->key($key), $default);
        } catch (Throwable $e) {
            return $default;
        }
    }
}
