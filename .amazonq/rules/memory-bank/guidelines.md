# Development Guidelines

## PHP Standards

Every PHP file must start with:
```php
<?php

declare(strict_types=1);  // pending: not yet on all files — add to all new files
```

- Always use explicit return types on all methods
- Always use typed parameters — avoid `mixed` unless unavoidable
- Use PHP 8 constructor property promotion:
  ```php
  public function __construct(protected ChatService $chatService) {}
  ```
- Use `throw_if` / `throw_unless` instead of manual `if + throw`:
  ```php
  throw_if(!$conversation, new ChatException('conversation not found.'));
  ```
- Use `abort_unless` for guard clauses in services:
  ```php
  abort_unless(config('onlyoffice.doc_server_url'), 500, 'Not configured.');
  ```
- Never use `dd()`, `dump()`, or `die()` — enforced by ArchitectureTest
- Use `env()` only inside `config/` files — everywhere else use `config('key')`
- Prefer `DB::transaction(fn() => ...)` over manual `beginTransaction/commit/rollBack`

## Naming Conventions

- Descriptive names: `isRegisteredForDiscounts`, not `discount()`
- Fix typos in variable/parameter names (e.g. `$currrentUserId` → `$currentUserId`)
- Enum keys must be TitleCase: `Admin`, `Customer`
- Cache keys namespaced: `"chat_stats:{conversationId}"`, `"page:{$page}"`
- Route names follow `resource.action`: `posts.index`, `chat.send.messages`

## Models

Define `$fillable` explicitly (never `$guarded = []`):
```php
protected $fillable = ['title', 'content', 'created_by', 'published_at', 'status'];
```

Define casts in a `casts(): array` method (Laravel 11+ style):
```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```

Use `Attribute::make()` for accessors/mutators:
```php
public function formattedPublishedAt(): Attribute
{
    return Attribute::make(
        get: fn () => $this->published_at?->format('d/m/Y')
    );
}
```

Scope methods type-hint `$query` as `Builder`:
```php
public function scopePublished($query): Builder
{
    return $query->whereNotNull('published_at');
}
```

Relationship methods declare return types:
```php
public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}
```

Scout searchable models implement `searchableAs()`, `toSearchableArray()`, and `shouldBeSearchable()`.

## Controllers

- Thin controllers — no business logic, no direct DB queries
- Inject `FormRequest` for all validation, inject services via constructor
- Return types declared: `View`, `RedirectResponse`, `JsonResponse`
- Use route model binding instead of manual `find()` calls
- Use `Gate::authorize()` for policy checks:
  ```php
  Gate::authorize('update', $post);
  ```
- Use `request()->validated()` when passing data to services

## Form Requests

Every FormRequest must include both `authorize()` and `rules()`:
```php
public function authorize(): bool { return true; }

public function rules(): array
{
    return [
        'title' => 'required|string|max:255|min:5',
        'content' => 'required|string|max:2000',
    ];
}
```

## Services

- Receive validated data arrays or DTOs — not raw `Request` objects (exception: ChatService currently receives `Request` — legacy pattern)
- All methods have explicit return types
- Use `Log::warning` / `Log::error` only — not `Log::info` for routine flow (currently violated in ChatService — do not repeat)
- Orchestrate repositories; no direct Eloquent queries

## Repositories

- All public methods declared in the corresponding interface in `App\Repositories\Interfaces\`
- Interface methods kept alphabetically ordered
- No business logic — data access only
- Remove empty `__construct()` methods (pending cleanup)
- Use `throw_if` for not-found cases:
  ```php
  $conversation = Conversation::find($id);
  throw_if(!$conversation, new ChatException('conversation not found.'));
  ```
- Bind interfaces to implementations in `AppServiceProvider::boot()`:
  ```php
  $this->app->bind(ChatRepositoryInterface::class, ChatRepository::class);
  ```

## Caching (Redis)

Always namespace cache keys:
```php
// Tagged cache for collections
cache()->tags(['posts', "user:{$userId}"])->remember("page:{$page}", now()->addMinutes(10), fn() => ...);

// Keyed cache for aggregates
cache()->store('redis')->remember("chat_stats:{$conversationId}", now()->addMinutes(5), fn() => ...);
```

Invalidate immediately after writes:
```php
cache()->tags(['posts', "user:{$userId}"])->flush();
cache()->store('redis')->forget("chat_stats:{$conversation->id}");
```

Default TTL: 5 minutes for aggregates, 10 minutes for paginated listings.

## Events & Broadcasting

- Every broadcast event implements `ShouldBroadcast`
- Use `->toOthers()` when sender should not receive their own broadcast:
  ```php
  broadcast(new ChatRead($chat))->toOthers();
  ```
- Event constructors fully typed — no raw arrays

## Routes

- Group related routes under named middleware groups
- Use `Route::resource()` for standard CRUD
- Route names follow `resource.action`: `posts.index`, `chat.send.messages`
- Inline closures only for trivial one-liners (redirects, simple webhooks)
- Pending: move inline notification deletion closure to a `NotificationController`

## Support Classes

Pure utility classes go in `app/Support/`. They use static methods and no framework dependencies:
```php
class OnlyOfficeJwt
{
    public static function encode(array $payload, string $secret): string { ... }
    public static function decode(string $token, string $secret): array { ... }
}
```

## Traits

- Traits used for cross-cutting concerns (media storage, etc.)
- Pending rename: `Helper` → `HandlesMediaStorage`
- Pending: move `MEDIA_PATH` constant to `config/`
- Remove empty `__construct()` methods from traits

## Testing (Pest 4)

### File Structure
```php
covers(PostController::class);  // links test to class for mutation testing

// File-scoped helpers (not in Pest.php)
function fakePost(int $count = 1): Collection|Post { ... }
function actAsCustomer(?User $user = null): TestCase { ... }
```

### Global Helpers (tests/Pest.php)
```php
function fakeUser(): User  // available in all Feature tests
expect()->extend('redirectToLogin', function () { ... });  // custom expectation
```

### Test Patterns
```php
// Authenticated test
test('store() store the post', function () {
    $user = fakeUser();
    $response = actAsCustomer($user)->post(route('posts.store'), [...]);
    $response->assertStatus(302)->assertRedirect(route('posts.index'));
    $this->assertDatabaseHas('posts', ['created_by' => $user->id]);
})->group('auth');

// Guest redirect test using custom expectation
test('unable to store the post', function () {
    $response = post(route('posts.store'), [...]);
    expect($response)->redirectToLogin();
});

// Authorization test
test('failed to update without permission', function () {
    $response = actAsCustomer($guestUser)->put(route('posts.update', $post->id), [...]);
    $response->assertForbidden(403);
})->group('auth', 'unauthorized');

// Parameterized validation test
it('fail to store without title and content', function (?string $title, ?string $content) {
    $response = actAsCustomer()->from(route('posts.create'))->post(route('posts.store'), [...]);
    $response->assertSessionHasErrors([...]);
})->with([
    [null, 'test content'],
    ['test title', null],
])->group('validation');
```

### Test Organization
- Use `describe()` blocks to group related tests (e.g. `describe('guest user', ...)`)
- Use `->group()` to tag: `auth`, `unauthorized`, `validation`, `authorized`
- Use `expect()` assertions over `$this->assert*` where possible
- Use specific assertion methods: `assertForbidden()`, `assertNotFound()` — not `assertStatus(403)`
- Higher-order testing for simple assertions:
  ```php
  test('create() show page')->actAsCustomer()->get('/posts/create')->assertStatus(200);
  ```

### Architecture Tests
```php
arch()->expect('App')->not->toUse(['die', 'dd', 'dump']);
arch()->preset()->php();
arch('app')->expect('App')->not->toHaveFileSystemPermissions('0777');
// Pending: ->toUseStrictTypes() once all files have declare(strict_types=1)
```

### Test Environment
- SQLite in-memory database
- `QUEUE_CONNECTION=sync` (jobs run synchronously)
- `BROADCAST_CONNECTION=null`
- `CACHE_STORE=array`
- `MAIL_MAILER=array`

## Frontend (Blade + Alpine + Tailwind)

### Alpine Components
Define Alpine components as named functions for reuse:
```js
function chatComponent(initialId) {
    return {
        conversationId: initialId,
        messages: [],
        isLoading: false,
        // ...
        init() { ... },
    }
}
```

Use `x-data`, `x-ref`, `x-show`, `x-model`, `x-for`, `x-if`, `x-transition` directives.

Use `Alpine.store()` for shared state across components:
```js
Alpine.store('chatSelection').selected
Alpine.store('chatSelection').toggle()
```

Use `$nextTick()` before DOM-dependent operations:
```js
this.$nextTick(() => { container.scrollTop = container.scrollHeight; });
```

Use `$dispatch()` for custom events between components:
```js
this.$dispatch('set-lastMessage', { message: e.message });
```

Use `x-intersect` (from `@alpinejs/intersect`) for scroll-based triggers:
```html
<div x-intersect:enter="loadMoreMessages()">...</div>
```

### Blade Components
Use `@props` for component props:
```blade
@props(['conversationId', 'currentUserId', 'selectedUserId'])
```

Use `{{ route('name') }}` inside Blade for URL generation in inline JS.

### Tailwind
- Use only Tailwind v3 classes
- Use `gap-*` for flex/grid spacing, not margins
- Support `dark:` variants if existing pages use dark mode
- Use `transition`, `duration-*`, `ease-*` for animations

## Code Style

- Run `vendor/bin/pint --dirty` before finalizing any PHP changes
- Always use curly braces for control structures, even single-line
- Prefer PHPDoc blocks over inline comments; only comment truly complex logic
- PHPDoc array shapes typed when shape is known:
  ```php
  /** @return array{unReadCount: int, latestMessage: string|null} */
  ```
- Use `sprintf()` for complex string formatting:
  ```php
  $newFilename = sprintf('%s-%s.%s', $basename, now()->format('YmdHis'), $extension);
  ```

## Pending Improvements (Do Not Repeat These Patterns)

1. Add `declare(strict_types=1)` to all PHP files
2. Remove empty `__construct()` methods from Repositories and Traits
3. Rename `Helper` trait → `HandlesMediaStorage`; move `MEDIA_PATH` to `config/`
4. Type-hint all `$query` parameters in scope methods as `Builder`
5. Replace `const ADMIN_ROLE / CUSTOMER_ROLE` in `User` with a `UserRole` enum
6. Move inline notification deletion closure in `web.php` to `NotificationController`
7. Replace `DB::beginTransaction/commit/rollBack` in `ChatService::sendMessage` with `DB::transaction(fn() => ...)`
8. Remove all commented-out code blocks (dead routes, `dd()` calls, unused stubs)
9. Enable `->toUseStrictTypes()` in `ArchitectureTest` once all files have strict types
