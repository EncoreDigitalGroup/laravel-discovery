<?php

namespace EncoreDigitalGroup\LaravelDiscovery\Providers;

use EncoreDigitalGroup\LaravelDiscovery\Console\Commands\DiscoverInterfaceImplementationsCommand;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class DiscoveryServiceProvider extends BaseServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->commands([
            DiscoverInterfaceImplementationsCommand::class,
        ]);
    }
}
