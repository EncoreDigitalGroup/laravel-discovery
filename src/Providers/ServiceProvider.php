<?php

namespace EncoreDigitalGroup\PackageTemplate\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . "/../../config/config.php", "template");
    }

    public function boot(): void {}
}
