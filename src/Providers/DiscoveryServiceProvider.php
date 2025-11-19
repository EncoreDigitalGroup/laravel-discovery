<?php

namespace EncoreDigitalGroup\LaravelDiscovery\Providers;

use EncoreDigitalGroup\LaravelDiscovery\Console\Commands\DiscoverInterfaceImplementationsCommand;
use EncoreDigitalGroup\LaravelDiscovery\Services\DiscoveryService;
use EncoreDigitalGroup\LaravelDiscovery\Support\Config\DiscoveryConfig;
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class DiscoveryServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app->singleton(DiscoveryService::class, function ($app) {
                return new DiscoveryService(
                    Discovery::config(),
                    new OutputStyle($app["console.input"], $app["console.output"])
                );
            });
        }
    }

    public function boot(): void
    {
        $this->commands([
            DiscoverInterfaceImplementationsCommand::class,
        ]);
    }
}
