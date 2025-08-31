<?php

use EncoreDigitalGroup\LaravelDiscovery\Support\Config\DiscoveryConfig;
use Tests\TestHelpers\AnotherTestInterface;
use Tests\TestHelpers\TestInterface;

beforeEach(function (): void {
    // Mock base_path function if it doesn't exist
    if (!function_exists("base_path")) {
        function base_path($path = ""): string
        {
            return "/mock/base/path" . ($path ? "/" . ltrim($path, "/") : "");
        }
    }

    $this->config = new DiscoveryConfig;
});

describe("Discovery Config Tests", function (): void {
    test("constructor sets default cache path", function (): void {
        $expectedPath = base_path("bootstrap/cache/discovery");

        expect($this->config->cachePath)->toEqual($expectedPath);
    });

    test("constructor initializes empty vendors array", function (): void {
        expect($this->config->vendors)->toEqual([]);
    });

    test("constructor initializes empty interfaces array", function (): void {
        expect($this->config->interfaces)->toEqual([]);
    });

    test("only adds existing interfaces", function (): void {
        $this->config
            ->addInterface(TestInterface::class)
            ->addInterface("NonExistentInterface")
            ->addInterface(AnotherTestInterface::class);

        expect($this->config->interfaces)->toEqual(["TestInterface", "AnotherTestInterface"]);
    });

    test("addVendor enables vendor search and adds vendor", function (): void {
        $result = $this->config->addVendor("test-vendor");

        expect($result)->toBe($this->config)
            ->and($this->config->vendors)->toContain("test-vendor")
            ->and($this->config->shouldSearchVendors())->toBeTrue();
    });

    test("searchVendors can be enabled and disabled", function (): void {
        expect($this->config->shouldSearchVendors())->toBeFalse();

        $result = $this->config->searchVendors();
        expect($result)->toBe($this->config)
            ->and($this->config->shouldSearchVendors())->toBeTrue();

        $this->config->searchVendors(false);
        expect($this->config->shouldSearchVendors())->toBeFalse();
    });

    test("searchAllVendors can be enabled and disabled", function (): void {
        expect($this->config->shouldSearchAllVendors())->toBeFalse();

        $result = $this->config->searchAllVendors();
        expect($result)->toBe($this->config)
            ->and($this->config->shouldSearchAllVendors())->toBeTrue();

        $this->config->searchAllVendors(false);
        expect($this->config->shouldSearchAllVendors())->toBeFalse();
    });

    test("searchVendors defaults to true when called without parameter", function (): void {
        $this->config->searchVendors();
        expect($this->config->shouldSearchVendors())->toBeTrue();
    });

    test("searchAllVendors defaults to true when called without parameter", function (): void {
        $this->config->searchAllVendors();
        expect($this->config->shouldSearchAllVendors())->toBeTrue();
    });

    test("method chaining works with vendor methods", function (): void {
        $result = $this->config
            ->addVendor("vendor1")
            ->searchVendors()
            ->searchAllVendors()
            ->addVendor("vendor2");

        expect($result)->toBe($this->config)
            ->and($this->config->vendors)->toEqual(["vendor1", "vendor2"])
            ->and($this->config->shouldSearchVendors())->toBeTrue()
            ->and($this->config->shouldSearchAllVendors())->toBeTrue();
    });
});