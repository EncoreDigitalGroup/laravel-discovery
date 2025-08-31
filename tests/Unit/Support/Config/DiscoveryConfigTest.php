<?php

use EncoreDigitalGroup\LaravelDiscovery\Support\Config\DiscoveryConfig;
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use EncoreDigitalGroup\StdLib\Exceptions\FilesystemExceptions\DirectoryNotFoundException;
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

    mkdir(__DIR__ . "/vendor/laravel", recursive: true);
});

afterEach(function (): void {
    rmdir(__DIR__ . "/vendor/laravel");
});

describe("DiscoveryConfig", function (): void {
    test("constructor sets default cache path", function (): void {
        $expectedPath = base_path("bootstrap/cache/discovery");

        expect(Discovery::config()->cachePath)->toEqual($expectedPath);
    });

    test("constructor initializes empty vendors array", function (): void {
        expect(Discovery::config()->vendors)->toEqual([]);
    });

    test("constructor initializes empty interfaces array", function (): void {
        expect(Discovery::config()->interfaces)->toEqual([]);
    });

    test("only adds existing interfaces", function (): void {
        Discovery::refresh()
            ->addInterface(TestInterface::class)
            ->addInterface("NonExistentInterface")
            ->addInterface(AnotherTestInterface::class);

        expect(Discovery::config()->interfaces)->toEqual(["TestInterface", "AnotherTestInterface"]);
    });

    test("addVendor throws exception when vendor directory does not exist", function (): void {
        expect(fn(): DiscoveryConfig => Discovery::refresh()->addVendor("TestVendor"))
            ->toThrow(DirectoryNotFoundException::class);
    });

    test("addVendor enables vendor search and adds vendor", function (): void {
        $result = Discovery::refresh()->addVendor("laravel");

        expect($result)->toBe(Discovery::config())
            ->and(Discovery::config()->vendors)->toContain("laravel")
            ->and(Discovery::config()->shouldSearchVendors())->toBeTrue();
    });

    test("addVendor converts vendor name to lowercase", function (): void {
        $result = Discovery::refresh()->addVendor("Laravel");

        expect($result)->toBe(Discovery::config())
            ->and(Discovery::config()->vendors)->toContain("laravel")
            ->and(Discovery::config()->vendors)->not->toContain("Laravel")
            ->and(Discovery::config()->shouldSearchVendors())->toBeTrue();
    });

    test("addVendor prevents duplicate vendors", function (): void {
        Discovery::refresh()
            ->addVendor("laravel")
            ->addVendor("Laravel");

        expect(Discovery::config()->vendors)->toHaveCount(1)
            ->and(Discovery::config()->vendors)->toContain("laravel");
    });

    test("addVendor prevents duplicate vendors with different case", function (): void {
        Discovery::refresh()
            ->addVendor("Laravel")
            ->addVendor("laravel")
            ->addVendor("LARAVEL");

        expect(Discovery::config()->vendors)->toHaveCount(1)
            ->and(Discovery::config()->vendors)->toContain("laravel");
    });

    test("searchVendors can be enabled and disabled", function (): void {
        expect(Discovery::refresh()->shouldSearchVendors())->toBeFalse();

        $result = Discovery::config()->searchVendors();
        expect($result)->toBe(Discovery::config())
            ->and(Discovery::config()->shouldSearchVendors())->toBeTrue();

        Discovery::config()->searchVendors(false);
        expect(Discovery::config()->shouldSearchVendors())->toBeFalse();
    });

    test("searchAllVendors can be enabled and disabled", function (): void {
        expect(Discovery::refresh()->shouldSearchAllVendors())->toBeFalse();

        $result = Discovery::config()->searchAllVendors();
        expect($result)->toBe(Discovery::config())
            ->and(Discovery::config()->shouldSearchAllVendors())->toBeTrue();

        Discovery::config()->searchAllVendors(false);
        expect(Discovery::config()->shouldSearchAllVendors())->toBeFalse();
    });

    test("method chaining works with vendor methods", function (): void {
        $result = Discovery::refresh()
            ->searchVendors()
            ->searchAllVendors()
            ->addVendor("laravel");

        expect($result)->toBe(Discovery::config())
            ->and(Discovery::config()->vendors)->toEqual(["laravel"])
            ->and(Discovery::config()->shouldSearchVendors())->toBeTrue()
            ->and(Discovery::config()->shouldSearchAllVendors())->toBeTrue();
    });

    test("addInterface prevents duplicate interfaces", function (): void {
        Discovery::refresh()
            ->addInterface(TestInterface::class)
            ->addInterface(TestInterface::class);

        expect(Discovery::config()->interfaces)->toEqual(["TestInterface"]);
    });

    test("invalid interface not added to interfaces array", function (): void {
        Discovery::refresh()
            ->addInterface("")
            ->addInterface("FakeInterface");

        expect(Discovery::config()->interfaces)->not->toContain("", "FakeInterface");
    });
});