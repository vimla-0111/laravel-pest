# Redis Configuration Guide

This application is pre-configured to use **Redis** for caching, queues, and sessions. Follow these steps to enable Redis.

## Prerequisites

- Redis server installed and running (see "Installation" section below)
- PHP Redis client already configured via `predis/predis` in `composer.json`

## Step 1: Install Dependencies

Ensure Predis is installed:

```bash
composer install
```

## Step 2: Configure `.env`

Copy environment variables from `.env.example` and update your `.env` file:

```dotenv
# Redis Connection
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Enable Redis for Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

## Step 3: Start Redis Server

### Using systemctl (Linux/systemd)
```bash
sudo systemctl start redis-server
sudo systemctl status redis-server
```

### Using Docker
```bash
docker run -d -p 6379:6379 redis:latest
```

### Using Homebrew (macOS)
```bash
brew services start redis
```

### Manual (any OS)
```bash
redis-server
```

## Step 4: Clear Application Config

```bash
php artisan config:clear
php artisan cache:clear
```

## Step 5: Restart Queue Worker (if applicable)

If using queue jobs:

```bash
php artisan queue:restart
```

Or for development with the built-in server:

```bash
composer run dev
```

## Step 6: Verify Redis Connection

```bash
php artisan tinker
Redis::connection('default')->ping()
```

Should return `"PONG"`.

## Configuration Details

- **Default database**: `REDIS_DB=0` (general cache/store)
- **Cache database**: `REDIS_CACHE_DB=1` (isolated cache storage)
- **Client**: `predis` (pure PHP, no extension required)
- **Host**: `127.0.0.1` (localhost)
- **Port**: `6379` (default Redis port)

## Troubleshooting

**Connection refused?**
- Ensure Redis server is running: `redis-cli ping`

**Still using database cache?**
- Verify `.env` contains `CACHE_STORE=redis`
- Run `php artisan config:clear`

**Predis not loading?**
- Run `composer install` again
- Check `vendor/predis/predis` exists

## References

- [Laravel Redis Documentation](https://laravel.com/docs/12/redis)
- [Predis Documentation](https://github.com/predis/predis)
