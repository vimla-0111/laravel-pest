# Product Overview

## What This App Is
A full-stack Laravel 12 web application serving as a learning/demo platform. It demonstrates real-world patterns across multiple feature domains.

## Key Features

### Blog / Post Management
- Create, edit, delete, and publish posts
- Post search powered by Laravel Scout + Typesense (published posts only)
- Authorization via PostPolicy (gate-based)
- Redis-tagged cache for paginated post listings (`posts`, `user:{id}` tags)
- Queue-dispatched email notifications on new post creation

### Real-Time Private Chat
- Conversation-based private messaging between users
- Media/file sharing in chat (stored on `local` disk)
- Read receipts (`read_at` tracking on chats)
- Real-time broadcasting via Laravel Reverb (WebSockets)
- Redis-keyed cache for chat stats (`chat_stats:{conversationId}`)
- Bulk chat deletion support

### Document Editing (OnlyOffice)
- Embedded OnlyOffice document editor integration
- JWT-signed document tokens via `firebase/php-jwt`
- Temporary signed URLs for document file access
- Callback endpoint (CSRF-exempt) for OnlyOffice save events

### Product Catalog
- Schema-driven product structure via `resources/schemas/product.schema.yml`
- Product model with factory and seeder

### Performance & Monitoring
- Laravel Pulse dashboard at `/performance-statistics`
- Laravel Nightwatch query filtering (excludes jobs, cache, sessions queries)

### Scheduling / Booking UI Demos
- `/day-schedule` — day schedule view demo
- `/cal-schedule` — calendar view demo
- Booking webhook callback endpoint

## User Roles
- `admin` — administrative access
- `customer` — standard authenticated user (most features scoped to this role)
- Roles stored as plain strings on `users.role`; `UserRole` enum is planned

## Authentication
- Laravel Breeze (session-based, `auth:web` middleware)
- Email verification required for dashboard access (`verified` middleware)
- Unauthenticated requests redirect to `route('login')`

## Target Users
- Developers learning Laravel patterns (services, repositories, events, jobs, broadcasting)
- Teams evaluating real-time chat, document editing, and search integrations
