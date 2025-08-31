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

        // If we get here without exception, registration worked
        expect(true)->toBeTrue();
    });

    test("service provider boots without error", function (): void {
        $this->serviceProvider->boot();

        expect(true)->toBeTrue();
    });

    test("service provider can be instantiated", function (): void {
        $provider = new DiscoveryServiceProvider($this->app);

        expect($provider)->toBeInstanceOf(DiscoveryServiceProvider::class);
    });

    test("service provider has required methods", function (): void {
        expect(method_exists($this->serviceProvider, "register"))->toBeTrue()
            ->and(method_exists($this->serviceProvider, "boot"))->toBeTrue()
            ->and(is_callable([$this->serviceProvider, "register"]))->toBeTrue()
            ->and(is_callable([$this->serviceProvider, "boot"]))->toBeTrue();
    });

    test("service provider extends laravel base service provider", function (): void {
        expect($this->serviceProvider)->toBeInstanceOf(ServiceProvider::class);
    });
});