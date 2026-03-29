# Technology Stack

## Backend

| Technology | Version | Purpose |
|---|---|---|
| PHP | ^8.3 (rules) / ^8.2 (composer) | Runtime |
| Laravel Framework | ^12.0 | Core framework |
| Laravel Breeze | ^2.3 | Auth scaffolding (session-based) |
| Laravel Reverb | ^1.0 | WebSocket server |
| Laravel Scout | ^11.1 | Full-text search abstraction |
| Laravel Pulse | ^1.4 | Performance monitoring |
| Laravel Nightwatch | ^1.19 | Query monitoring/filtering |
| predis/predis | ^3.3 | Redis client (cache + queues) |
| firebase/php-jwt | ^7.0 | JWT tokens for OnlyOffice |
| spatie/image-optimizer | ^1.8 | Media optimization |
| typesense/typesense-php | ^6.0 | Typesense search driver |

## Frontend

| Technology | Version | Purpose |
|---|---|---|
| Livewire | 3.x | Reactive server-driven UI |
| Alpine.js | ^3.4.2 | Lightweight JS interactivity (bundled with Livewire) |
| @alpinejs/intersect | ^3.15.2 | Alpine intersection observer plugin |
| Tailwind CSS | ^3.1.0 | Utility-first CSS |
| @tailwindcss/forms | ^0.5.2 | Form styling plugin |
| Vite | ^7.0.7 | Asset bundler |
| laravel-vite-plugin | ^2.0.0 | Vite + Laravel integration |
| Laravel Echo | ^2.2.6 | WebSocket client |
| pusher-js | ^8.4.0 | Pusher/Reverb transport |
| axios | ^1.11.0 | HTTP client |

## Testing

| Technology | Version | Purpose |
|---|---|---|
| Pest | ^4.1 | Test framework |
| pest-plugin-laravel | ^4.0 | Laravel-specific Pest helpers |
| pest-plugin-browser | ^4.1 | Browser/E2E tests (Playwright) |
| pest-plugin-type-coverage | ^4.0 | Type coverage analysis |
| pest-plugin-mutate | (installed) | Mutation testing |
| pest-plugin-arch | (installed) | Architecture tests |
| Mockery | ^1.6 | Mocking |
| Playwright | ^1.57.0 | Browser automation |

## Dev Tools

| Tool | Purpose |
|---|---|
| Laravel Pint | Code style fixer (`vendor/bin/pint --dirty`) |
| Laravel Sail | Docker dev environment |
| Laravel Pail | Log tailing |
| PHPStan | Static analysis |
| grazulex/laravel-turbomaker | Code generation |
| laravel/boost | Dev utilities |
| concurrently | Run multiple processes in dev |

## Infrastructure

| Component | Technology |
|---|---|
| Queue driver | Redis |
| Cache driver | Redis |
| WebSockets | Laravel Reverb |
| Search | Typesense (via Laravel Scout) |
| Media storage | `local` disk (not `public`) |
| Database (production) | MySQL |
| Database (testing) | SQLite in-memory |

## Vite Entry Points

```js
// vite.config.js
input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/js/echo.js',  // Laravel Echo + Reverb WebSocket setup
]
```

## Development Commands

```bash
# Full dev stack (server + queue + logs + vite)
composer dev

# Setup from scratch
composer setup

# Run tests
composer test
php artisan test
php artisan test --filter=TestName
php artisan test tests/Feature/SomeTest.php

# Code style
vendor/bin/pint --dirty

# Frontend
npm run dev
npm run build

# Queue worker
php artisan queue:listen --tries=1

# Log tailing
php artisan pail --timeout=0
```

## Test Environment (phpunit.xml)

- `APP_ENV=testing`
- `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`
- `CACHE_STORE=array`
- `QUEUE_CONNECTION=sync`
- `BROADCAST_CONNECTION=null`
- `MAIL_MAILER=array`
- Pulse, Telescope, Nightwatch disabled
- `XDEBUG_MODE=coverage`
