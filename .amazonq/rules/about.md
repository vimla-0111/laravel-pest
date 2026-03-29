# Project Knowledge

## What This App Is
A full-stack Laravel 12 web application that serves as a learning/demo platform covering:
- Blog-style post management with publishing, search, and authorization
- Real-time private chat between users (conversations, messages, media sharing)
- Document editing integration (OnlyOffice)
- Product catalog with schema-driven structure
- Performance monitoring via Laravel Pulse
- Scheduling/booking UI demos (cal, day-schedule views)

## User Roles
- `admin` — administrative access
- `customer` — standard authenticated user (most features scoped to this role)
- Roles currently stored as plain strings on `users.role`; a `UserRole` enum is planned

## Authentication
- Laravel Breeze (session-based, `auth:web` middleware)
- Users must be verified (`verified` middleware on dashboard)
- Unauthenticated requests redirect to `route('login')`

## Infrastructure
- Queue driver: Redis (jobs processed via `queue:listen`)
- Cache driver: Redis (tagged cache for posts, keyed cache for chat stats)
- WebSockets: Laravel Reverb
- Search: Laravel Scout + Typesense
- Media storage: `local` disk (not `public`)
- Frontend: Blade + Livewire v3 + Alpine.js v3 + Tailwind CSS v3, bundled with Vite

## Test Helpers (tests/Pest.php)
- `fakeUser()` — creates a User via factory, available in all Feature tests
- `expect()->redirectToLogin()` — custom expectation asserting 302 → login
- `actAsCustomer()` is defined per-file in `PostTest.php` (file-scoped helper pattern)
