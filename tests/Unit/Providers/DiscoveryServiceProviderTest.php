<?php

use EncoreDigitalGroup\LaravelDiscovery\Providers\DiscoveryServiceProvider;
use Illuminate\Support\ServiceProvider;

beforeEach(function (): void {
    $this->app = app();
    $this->serviceProvider = new DiscoveryServiceProvider($this->app);
});

describe("DiscoveryServiceProvider", function (): void {
    test("service provider registers without error", function (): void {
        $this->serviceProvider->register();

        expect(true)->toBeTrue();
    });

    test("service provider boots without error", function (): void {
        $this->serviceProvider->boot();

        // If we get here without exception, boot worked
        expect(true)->toBeTrue();
    });

    test("service provider can be instantiated and extends laravel base service provider", function (): void {
        $provider = new DiscoveryServiceProvider($this->app);

        expect($provider)->toBeInstanceOf(DiscoveryServiceProvider::class)
            ->and($provider)->toBeInstanceOf(ServiceProvider::class);
    });

    test("service provider has required methods", function (): void {
        expect(method_exists($this->serviceProvider, "register"))->toBeTrue()
            ->and(method_exists($this->serviceProvider, "boot"))->toBeTrue()
            ->and(is_callable([$this->serviceProvider, "register"]))->toBeTrue()
            ->and(is_callable([$this->serviceProvider, "boot"]))->toBeTrue();
    });
});