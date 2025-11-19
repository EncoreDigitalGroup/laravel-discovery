<?php

namespace EncoreDigitalGroup\LaravelDiscovery\Providers;

use EncoreDigitalGroup\LaravelDiscovery\Console\Commands\DiscoverInterfaceImplementationsCommand;
use EncoreDigitalGroup\LaravelDiscovery\Services\DiscoveryService;
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class DiscoveryServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole() && !$this->isPackageDiscovery()) {
            $this->app->singleton(DiscoveryService::class, function ($app) {
                return new DiscoveryService(Discovery::config());
            });
        }
    }

    private function isPackageDiscovery(): bool
    {
        return in_array('package:discover', $_SERVER['argv'] ?? []);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole() && !$this->isPackageDiscovery()) {
            $this->commands([
                DiscoverInterfaceImplementationsCommand::class,
            ]);
        }
    }
}
