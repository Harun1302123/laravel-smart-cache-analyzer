<?php

namespace SmartCache\Analyzer;

use Illuminate\Support\ServiceProvider;
use SmartCache\Analyzer\Console\Commands\AnalyzeCacheCommand;
use SmartCache\Analyzer\Console\Commands\CleanupCacheCommand;
use SmartCache\Analyzer\Console\Commands\WarmCacheCommand;
use SmartCache\Analyzer\Services\CacheAnalyzer;
use SmartCache\Analyzer\Services\QueryMonitor;

class SmartCacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/smart-cache.php', 'smart-cache'
        );

        $this->app->singleton(CacheAnalyzer::class, function ($app) {
            return new CacheAnalyzer(
                $app['cache.store'],
                $app['db']
            );
        });

        $this->app->singleton(QueryMonitor::class, function ($app) {
            return new QueryMonitor(
                $app[CacheAnalyzer::class],
                config('smart-cache')
            );
        });

        $this->app->alias(CacheAnalyzer::class, 'smart-cache');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/smart-cache.php' => config_path('smart-cache.php'),
            ], 'smart-cache-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'smart-cache-migrations');

            $this->commands([
                AnalyzeCacheCommand::class,
                CleanupCacheCommand::class,
                WarmCacheCommand::class,
            ]);
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'smart-cache');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Start query monitoring if enabled
        if (config('smart-cache.enabled', true)) {
            $this->app[QueryMonitor::class]->start();
        }
    }
}
