<?php

use EncoreDigitalGroup\LaravelDiscovery\Support\Config\DiscoveryConfig;
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

    $this->config = new DiscoveryConfig;
});

describe("DiscoveryConfig", function (): void {
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

    test("addVendor throws exception when vendor directory does not exist", function (): void {
        expect(fn() => $this->config->addVendor("TestVendor"))
            ->toThrow(DirectoryNotFoundException::class);
    });

    test("addVendor enables vendor search and adds vendor", function (): void {
        $result = $this->config->addVendor("EncoreDigitalGroup");

        expect($result)->toBe($this->config)
            ->and($this->config->vendors)->toContain("encoredigitalgroup")
            ->and($this->config->shouldSearchVendors())->toBeTrue();
    });

    test("addVendor converts vendor name to lowercase", function (): void {
        $result = $this->config->addVendor("EncoreDigitalGroup");

        expect($result)->toBe($this->config)
            ->and($this->config->vendors)->toContain("encoredigitalgroup")
            ->and($this->config->vendors)->not->toContain("EncoreDigitalGroup")
            ->and($this->config->shouldSearchVendors())->toBeTrue();
    });

    test("addVendor prevents duplicate vendors", function (): void {
        $this->config
            ->addVendor("EncoreDigitalGroup")
            ->addVendor("EncoreDigitalGroup");

        expect($this->config->vendors)->toHaveCount(1)
            ->and($this->config->vendors)->toContain("encoredigitalgroup");
    });

    test("addVendor prevents duplicate vendors with different case", function (): void {
        $this->config
            ->addVendor("EncoreDigitalGroup")
            ->addVendor("encoredigitalgroup")
            ->addVendor("ENCOREDIGITALGROUP");

        expect($this->config->vendors)->toHaveCount(1)
            ->and($this->config->vendors)->toContain("encoredigitalgroup");
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

    test("method chaining works with vendor methods", function (): void {
        $result = $this->config
            ->addVendor("encoredigitalgroup")
            ->searchVendors()
            ->searchAllVendors()
            ->addVendor("laravel");

        expect($result)->toBe($this->config)
            ->and($this->config->vendors)->toEqual(["encoredigitalgroup", "laravel"])
            ->and($this->config->shouldSearchVendors())->toBeTrue()
            ->and($this->config->shouldSearchAllVendors())->toBeTrue();
    });

    test("addInterface prevents duplicate interfaces", function (): void {
        $this->config
            ->addInterface(TestInterface::class)
            ->addInterface(TestInterface::class);

        expect($this->config->interfaces)->toEqual(["TestInterface"]);
    });

    test("invalid interface not added to interfaces array", function (): void {
        $this->config
            ->addInterface("")
            ->addInterface("FakeInterface");

        expect($this->config->interfaces)->not->toContain("", "FakeInterface");
    });
});