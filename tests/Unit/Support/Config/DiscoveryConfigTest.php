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
        $expectedPath = base_path("boostrap/cache/discovery");

        expect($this->config->cachePath)->toEqual($expectedPath);
    });

    test("constructor initializes empty vendors array", function (): void {
        expect($this->config->vendors)->toEqual([]);
    });

    test("constructor initializes empty interfaces array", function (): void {
        expect($this->config->interfaces)->toEqual([]);
    });

    test("add vendor adds vendor to array", function (): void {
        $vendor = "test-vendor";

        $result = $this->config->addVendor($vendor);

        expect($result)->toBe($this->config)
            ->and($this->config->vendors)->toContain($vendor);
    });

    test("add vendor supports method chaining", function (): void {
        $result = $this->config->addVendor("vendor1")->addVendor("vendor2");

        expect($result)->toBe($this->config)
            ->and($this->config->vendors)->toEqual(["vendor1", "vendor2"]);
    });

    test("add interface adds interface to array", function (): void {
        $interface = TestInterface::class;

        $result = $this->config->addInterface($interface);

        expect($result)->toBe($this->config);
        // Since interface_exists() returns true for test interfaces,
        // the basename gets added to the interfaces array
        expect($this->config->interfaces)->toContain("TestInterface");
    });

    test("add interface supports method chaining", function (): void {
        $result = $this->config->addInterface(TestInterface::class)->addInterface(AnotherTestInterface::class);

        expect($result)->toBe($this->config);
        // Since interface_exists() returns true for test interfaces, basenames get added
        expect($this->config->interfaces)->toEqual(["TestInterface", "AnotherTestInterface"]);
    });

    test("can add multiple vendors and interfaces", function (): void {
        $this->config
            ->addVendor("vendor1")
            ->addVendor("vendor2")
            ->addInterface(TestInterface::class)
            ->addInterface(AnotherTestInterface::class);

        expect($this->config->vendors)->toEqual(["vendor1", "vendor2"]);
        // Since interface_exists() returns true for test interfaces, basenames get added
        expect($this->config->interfaces)->toEqual(["TestInterface", "AnotherTestInterface"]);
    });

    test("can add duplicate vendors", function (): void {
        $this->config->addVendor("vendor1")->addVendor("vendor1");

        expect($this->config->vendors)->toEqual(["vendor1", "vendor1"]);
    });

    test("can add duplicate interfaces", function (): void {
        $this->config->addInterface(TestInterface::class)->addInterface(TestInterface::class);

        // Since interface_exists() returns true, basenames get added (including duplicates)
        expect($this->config->interfaces)->toEqual(["TestInterface", "TestInterface"]);
    });

    test("add interface ignores non-existent interfaces", function (): void {
        $this->config->addInterface("NonExistentInterface");

        // Since interface_exists() returns false for non-existent interfaces, nothing gets added
        expect($this->config->interfaces)->toBeEmpty();
    });

    test("add interface only adds existing interfaces", function (): void {
        $this->config
            ->addInterface(TestInterface::class)
            ->addInterface("NonExistentInterface")
            ->addInterface(AnotherTestInterface::class);

        // Only existing interfaces get added
        expect($this->config->interfaces)->toEqual(["TestInterface", "AnotherTestInterface"]);
    });
});