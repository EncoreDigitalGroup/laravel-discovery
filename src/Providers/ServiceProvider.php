<?php

namespace EncoreDigitalGroup\LaravelDisovery\Providers;

use EncoreDigitalGroup\LaravelDiscovery\Console\Commands\DiscoverInterfaceImplementationsCommand;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void {
        $this->commands([
            DiscoverInterfaceImplementationsCommand::class
        ]);
    }
}
