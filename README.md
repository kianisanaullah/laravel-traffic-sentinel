# Traffic Sentinel

🚦 **Traffic Sentinel** is a lightweight Laravel package to track **real visitors vs bots**, online users, pageviews, referrers, and crawler activity — with a modern dashboard.

---

## ✨ Features

- ✅ Human vs Bot detection
- 👀 Online users (last N minutes)
- 📊 Pageviews (humans / all)
- 🔍 Top pages, bots, referrers
- 🧠 Bot detection via User-Agent
- 🚫 Exclude admin/internal URLs
- 🧹 Prune old data
- 🖥️ Beautiful dashboard (Bootstrap 5)
- ⚡ Zero JS framework dependency

---

## 📦 Installation

```bash
composer require kianisanaullah/traffic-sentinel
```

## Publish config & migrations:
php artisan vendor:publish --tag=traffic-sentinel-config
php artisan vendor:publish --tag=traffic-sentinel-migrations
php artisan migrate



## 🧩 Middleware
Add to web middleware group

\Kianisanaullah\TrafficSentinel\Http\Middleware\TrackTraffic::class,


## 📊 Dashboard
/admin/traffic-sentinel


## 🔐 Protect Dashboard

Protect it in config:
'dashboard' => [
  'middleware' => ['web', 'auth'],
],

## 🚫 Excluding URLs
'exclude' => [
  'paths' => ['admin', 'api', 'traffic-stats'],
],

## 🧹 Prune Old Data

php artisan traffic:prune --days=30


## 📄 License

MIT © Sanaullah Kiani
---

