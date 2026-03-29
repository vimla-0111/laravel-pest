# Project Rules

## Stack & Package Versions
- PHP 8.3, Laravel 12, Pest 4, PHPUnit 12
- Livewire 3, Alpine.js 3, Tailwind CSS 3, Vite
- Laravel Reverb 1 (WebSockets), Laravel Scout 11 + Typesense (search)
- Laravel Pulse 1, Laravel Nightwatch 1, Laravel Breeze 2
- Redis (cache + queues), predis/predis 3
- spatie/image-optimizer, firebase/php-jwt

---

## General Conventions (from Boost)
- Follow all existing code conventions — check sibling files before creating anything new
- Use descriptive names: `isRegisteredForDiscounts`, not `discount()`
- Do not create new top-level directories without approval
- Do not change dependencies without approval
- Only create documentation files when explicitly requested
- Run `vendor/bin/pint --dirty` before finalizing any PHP changes
- Always use curly braces for control structures, even single-line ones
- Prefer PHPDoc blocks over inline comments; only comment truly complex logic
- PHPDoc array shapes should be typed when the shape is known
- Enum keys must be TitleCase: `Admin`, `Customer`, not `ADMIN`, `CUSTOMER`

---

## PHP Standards
- Always add `declare(strict_types=1)` at the top of every PHP file
- Always use explicit return type declarations on all methods and functions
- Always use typed parameters — avoid `mixed` unless unavoidable
- Use PHP 8 constructor property promotion; never leave empty `__construct()` with no parameters
- Use `throw_if` / `throw_unless` instead of manual `if + throw`
- Prefer `DB::transaction(fn() => ...)` over manual `beginTransaction/commit/rollBack`
- Never use `dd()`, `dump()`, or `die()` in committed code (enforced by ArchitectureTest)
- Fix typos in variable/parameter names (e.g. `$currrentUserId` → `$currentUserId`)
- Use `env()` only inside `config/` files — everywhere else use `config('key')`

---

## Architecture: Controllers → Services → Repositories → Models
- Controllers must be thin: no business logic, no direct DB queries
- Services handle all business logic and orchestrate repositories
- Repositories handle all Eloquent/DB queries; always implement a corresponding interface in `App\Repositories\Interfaces\`
- Bind repository interfaces to implementations in `AppServiceProvider`
- Avoid `DB::` facades; prefer `Model::query()` and Eloquent relationships
- Always eager-load relationships to prevent N+1 queries
- Use `Model::query()` for complex queries; raw query builder only as last resort

---

## Models
- Always define `$fillable` (never `$guarded = []`)
- Define casts in a `casts(): array` method (Laravel 11+ style), not `$casts` property
- Scope methods must type-hint `$query` as `Builder`
- Relationship methods must declare return types (`HasMany`, `BelongsTo`, etc.)
- Use `Attribute::make()` for accessors/mutators (not `get{Name}Attribute`)
- Replace `const ADMIN_ROLE / CUSTOMER_ROLE` with a `UserRole` enum (TitleCase keys)
- When creating new models, also create factories and seeders

---

## Controllers & Requests
- Inject `FormRequest` classes for all validation — never validate in the controller body
- Return types must be declared: `Response`, `RedirectResponse`, `JsonResponse`
- Use route model binding instead of manual `find()` calls
- Include both `rules()` and `authorize()` in every Form Request
- Never pass raw `Request` objects into services — use validated data arrays or DTOs

---

## Services
- Receive validated data (arrays or DTOs), not raw `Request` objects
- All methods must have explicit return types
- Use `Log::warning` / `Log::error` only — not `Log::info` for routine flow

---

## Repositories
- All public methods must be declared in the interface
- Keep interface methods alphabetically ordered
- No business logic inside repositories — data access only
- Remove empty `__construct()` methods

---

## Queues & Jobs
- Use `ShouldQueue` for all time-consuming operations (email, notifications, heavy processing)

---

## Authentication & Authorization
- Use Laravel's built-in auth features: gates, policies, Sanctum where appropriate
- Prefer named routes with `route()` for all URL generation

---

## Caching (Redis)
- Always namespace cache keys: `"{resource}:{id}"` (e.g. `"chat_stats:{conversationId}"`)
- Invalidate cache immediately after any write that affects cached data
- Use tagged cache (`cache()->tags([...])`) for collections needing bulk invalidation
- Default TTL: 5 minutes for frequently-read aggregates

---

## Events & Broadcasting
- Every broadcast event must implement `ShouldBroadcast`
- Use `->toOthers()` when the sender should not receive their own broadcast
- Event constructors must be fully typed — no raw arrays

---

## Routes
- Group related routes under a named `middleware` group
- Use `Route::resource()` for standard CRUD; add only the extra routes needed
- Route names must follow `resource.action` convention: `posts.index`, `chat.send.messages`
- Inline closures in routes only for trivial one-liners (redirects, simple webhooks)

---

## Frontend (Blade + Livewire + Alpine + Tailwind)
- Livewire components require a single root element
- Use `wire:model.live` for real-time updates (`wire:model` is deferred by default in v3)
- Use `wire:loading` and `wire:dirty` for loading states
- Add `wire:key` on all loop elements
- Use `$this->dispatch()` for Livewire events (not `emit`)
- Alpine is bundled with Livewire — do not manually include it
- Use Tailwind `gap-*` utilities for spacing in flex/grid layouts, not margins
- Support `dark:` variants if existing pages already use dark mode
- Use only Tailwind v3 classes
- If a frontend change isn't reflected, the user may need to run `npm run build` or `npm run dev`
- All JS utility functions must include JSDoc comments

---

## Testing (Pest 4)
- Every change must be covered by a new or updated test — run affected tests before finalizing
- All tests use Pest 4 — create with `php artisan make:test --pest {Name}`
- Feature tests extend `Tests\TestCase` with `RefreshDatabase`
- Unit tests use `uses(Tests\TestCase::class)->in('Unit')`
- Global helpers (e.g. `fakeUser()`) go in `tests/Pest.php`; file-scoped helpers stay in the test file
- Use `describe()` blocks to group related tests (e.g. guest vs authenticated)
- Use `->group()` to tag tests: `auth`, `unauthorized`, `validation`
- Use `covers(ClassName::class)` at the top of feature test files
- Use `expect()` assertions over `$this->assert*` where possible
- Use `->with()` datasets for parameterised tests (especially validation rules)
- Use specific assertion methods: `assertForbidden()`, `assertNotFound()` — not `assertStatus(403)`
- Architecture tests live in `tests/Feature/ArchitectureTest.php`
- Browser tests live in `tests/Browser/` (Pest v4 browser plugin)
- Run minimum tests needed: `php artisan test --filter=testName` or `php artisan test tests/Feature/SomeTest.php`
- Do not remove any test files without explicit approval
- Do not use tinker to verify functionality that tests already cover

---

## Pending Improvements
1. Add `declare(strict_types=1)` to all PHP files; then enable `->toUseStrictTypes()` in `ArchitectureTest`
2. Remove all empty `__construct()` methods across Repositories and Traits
3. Rename `Helper` trait → `HandlesMediaStorage`; move `MEDIA_PATH` constant to `config/`
4. Type-hint all `$query` parameters in scope methods as `Builder`
5. Replace `const ADMIN_ROLE / CUSTOMER_ROLE` in `User` with a `UserRole` enum
6. Move the inline notification deletion closure in `web.php` to a `NotificationController`
7. Add `StorePostRequest` / `UpdatePostRequest` Form Requests for `PostController`
8. Add `pest-plugin-mutate` mutation tests for `PostService` and `ChatService`
9. Remove all commented-out code blocks (dead routes, `dd()` calls, unused stubs)
10. Replace `DB::beginTransaction/commit/rollBack` in `ChatService::sendMessage` with `DB::transaction(fn() => ...)`
