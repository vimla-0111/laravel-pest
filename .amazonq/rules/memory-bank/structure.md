# Project Structure

## Directory Layout

```
laravel-pest/
├── app/
│   ├── Console/Commands/        # Artisan commands
│   ├── Events/                  # Broadcast events (Reverb/WebSockets)
│   │   ├── ChatDeleted.php
│   │   ├── ChatRead.php
│   │   ├── MessageSent.php
│   │   ├── SentPrivateMessage.php
│   │   └── UserConversation.php
│   ├── Exceptions/
│   │   └── ChatException.php    # Domain-specific exception
│   ├── Http/
│   │   ├── Controllers/         # Thin controllers — no business logic
│   │   ├── Requests/            # FormRequest classes for all validation
│   │   └── Resources/           # API resources
│   ├── Jobs/
│   │   └── NotifyUserOfNewPost.php  # Queued job (Redis)
│   ├── Mail/
│   │   └── UserNewPostNotification.php
│   ├── Models/                  # Eloquent models
│   │   ├── Chat.php
│   │   ├── Conversation.php
│   │   ├── ConversationUser.php
│   │   ├── Message.php
│   │   ├── Post.php
│   │   ├── Product.php
│   │   └── User.php
│   ├── Notifications/
│   │   └── NewPost.php
│   ├── Policies/
│   │   └── PostPolicy.php       # Gate-based authorization
│   ├── Providers/
│   │   └── AppServiceProvider.php  # Repository bindings, policy registration
│   ├── Repositories/
│   │   ├── Interfaces/          # Repository interfaces (alphabetically ordered methods)
│   │   │   ├── ChatRepositoryInterface.php
│   │   │   └── UserRepositoryInterface.php
│   │   ├── ChatRepository.php
│   │   └── UserRepository.php
│   ├── Services/
│   │   ├── ChatService.php      # Chat business logic
│   │   ├── OnlyOfficeService.php
│   │   └── PostService.php
│   ├── Support/
│   │   └── OnlyOfficeJwt.php    # JWT token generation for OnlyOffice
│   ├── Traits/
│   │   └── Helper.php           # Media storage helpers (planned rename: HandlesMediaStorage)
│   └── View/Components/         # Blade view components
├── config/
│   ├── onlyoffice.php           # OnlyOffice-specific config
│   ├── reverb.php               # WebSocket config
│   ├── scout.php                # Typesense search config
│   └── pulse.php                # Performance monitoring config
├── database/
│   ├── factories/               # Model factories (Post, Product, User)
│   ├── migrations/              # Timestamped migrations
│   └── seeders/
│       ├── AdminSeeder.php
│       └── DatabaseSeeder.php
├── resources/
│   ├── js/
│   │   ├── app.js
│   │   ├── bootstrap.js
│   │   └── echo.js              # Laravel Echo + Reverb setup
│   ├── schemas/
│   │   └── product.schema.yml   # Schema-driven product structure
│   └── views/
│       ├── auth/                # Breeze auth views
│       ├── components/          # Blade components (chat, etc.)
│       ├── documents/           # OnlyOffice editor views
│       ├── layouts/             # App layout templates
│       ├── posts/               # Post CRUD views
│       ├── product/             # Product catalog views
│       ├── chat_page.blade.php  # Full chat UI
│       ├── chat.blade.php       # Simple chat view
│       └── dashboard.blade.php
├── routes/
│   ├── web.php                  # All web routes
│   ├── auth.php                 # Breeze auth routes
│   ├── channels.php             # Broadcast channel authorization
│   └── console.php              # Scheduled commands
└── tests/
    ├── Feature/
    │   ├── Auth/                # Breeze auth tests
    │   ├── Services/            # Service-layer tests
    │   ├── ArchitectureTest.php # PHPUnit architecture assertions
    │   ├── PostTest.php
    │   ├── ProductTest.php
    │   ├── DocumentEditorTest.php
    │   ├── PublishedPostSearchTest.php
    │   └── RedisIntegrationTest.php
    ├── Unit/
    │   └── ProductUnitTest.php
    ├── Pest.php                 # Global helpers and custom expectations
    └── TestCase.php
```

## Architecture: Controllers → Services → Repositories → Models

```
Request → FormRequest (validation) → Controller → Service → Repository → Model
                                         ↓
                                    Response/View
```

- Controllers: thin, inject services, return typed responses (`View`, `RedirectResponse`, `JsonResponse`)
- Services: all business logic, receive validated arrays (not raw `Request`), orchestrate repositories
- Repositories: data access only, implement interfaces in `App\Repositories\Interfaces\`
- Models: define `$fillable`, `casts()` method, scopes, relationships, accessors via `Attribute::make()`

## Key Relationships

- `User` hasMany `Post` (via `created_by`), hasMany `Chat` (via `sender_id`), belongsToMany `Conversation`
- `Conversation` hasMany `Chat`, belongsToMany `User` (via `conversation_users` pivot)
- `Post` belongsTo `User` (creator), uses Scout `Searchable` trait

## Dependency Injection & Bindings

Repository interfaces bound in `AppServiceProvider::boot()`:
- `ChatRepositoryInterface` → `ChatRepository`
- `UserRepositoryInterface` → `UserRepository`

Services injected via constructor property promotion in controllers:
```php
public function __construct(protected ChatService $chatService) {}
```

## Caching Strategy

- Post listings: `cache()->tags(['posts', "user:{$userId}"])->remember(...)` — tagged Redis cache
- Chat stats: `cache()->store('redis')->remember("chat_stats:{$conversationId}", ...)` — keyed Redis cache
- Cache invalidated immediately after writes that affect cached data

## Broadcasting

- Events implement `ShouldBroadcast`
- `->toOthers()` used when sender should not receive their own broadcast
- Channel authorization in `routes/channels.php`
