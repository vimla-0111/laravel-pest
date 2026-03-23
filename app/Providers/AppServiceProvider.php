<?php

namespace App\Providers;

use App\Models\Post;
use App\Policies\PostPolicy;
use App\Repositories\ChatRepository;
use App\Repositories\Interfaces\ChatRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;
use DateTimeInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Nightwatch\Facades\Nightwatch;
use Laravel\Nightwatch\Records\Query;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Post::class, PostPolicy::class);
        Context::add('locale', App::currentLocale());

        Nightwatch::rejectQueries(function (Query $query) {
            return str_contains($query->sql, 'into `jobs`');
        });

        Nightwatch::rejectQueries(function (Query $query) {
            return str_contains($query->sql, 'from `cache`')
                || str_contains($query->sql, 'into `cache`');
        });

        Nightwatch::rejectQueries(function (Query $query) {
            return str_contains($query->sql, 'from `sessions`')
                || str_contains($query->sql, 'into `sessions`');
        });

        Storage::disk(config('onlyoffice.storage_disk', 'public'))
            ->buildTemporaryUrlsUsing(function (string $path, DateTimeInterface $expiration, array $options): string {
                return URL::temporarySignedRoute(
                    'documents.file',
                    $expiration,
                    array_merge($options, ['path' => $path])
                );
            });

        $this->app->bind(ChatRepositoryInterface::class, ChatRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }
}
