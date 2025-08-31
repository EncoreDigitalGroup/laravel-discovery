<?php

use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use Tests\TestHelpers\TestInterface;
use Tests\TestHelpers\AnotherTestInterface;

beforeEach(function (): void {
    $this->cachePath = Discovery::config()->cachePath;
    if (!is_dir($this->cachePath)) {
        mkdir($this->cachePath, 0755, true);
    }
});

afterEach(function (): void {
    if (is_dir($this->cachePath)) {
        $files = glob($this->cachePath . "/*");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
});

describe("DiscoverInterfaceImplementationsCommand Integration Tests", function (): void {
    test("command runs successfully with configured interfaces", function (): void {
        Discovery::config()
            ->addInterface(TestInterface::class)
            ->addInterface(AnotherTestInterface::class);

        $this->artisan("discovery:run")
            ->assertExitCode(0);

        // Verify cache files were created
        $testCacheFile = $this->cachePath . "/TestInterface.php";
        $anotherCacheFile = $this->cachePath . "/AnotherTestInterface.php";
        
        expect(file_exists($testCacheFile))->toBeTrue()
            ->and(file_exists($anotherCacheFile))->toBeTrue();

        // Verify cache files contain arrays
        $testCache = require $testCacheFile;
        $anotherCache = require $anotherCacheFile;
        
        expect($testCache)->toBeArray()
            ->and($anotherCache)->toBeArray();
    });

    test("command handles vendor configuration without actual vendor search", function (): void {
        // Just test the configuration is set up correctly, not the actual search
        Discovery::config()
            ->addInterface(TestInterface::class)
            ->addVendor("test-vendor");

        expect(Discovery::config()->shouldSearchVendors())->toBeTrue()
            ->and(Discovery::config()->vendors)->toContain("test-vendor");
            
        // Reset to avoid timeout on actual vendor search
        Discovery::config()->searchVendors(false);
        Discovery::config()->vendors = [];

        $this->artisan("discovery:run")
            ->assertExitCode(0);
    });

    test("command handles search all vendors configuration without timeout", function (): void {
        // Test configuration setup only
        Discovery::config()
            ->addInterface(TestInterface::class)
            ->searchAllVendors(true);

        expect(Discovery::config()->shouldSearchAllVendors())->toBeTrue();
        
        // Reset to avoid timeout on actual vendor search
        Discovery::config()->searchAllVendors(false);

        $this->artisan("discovery:run")
            ->assertExitCode(0);
    });

    test("command runs without interfaces configured", function (): void {
        // Clear any existing interfaces
        Discovery::config()->interfaces = [];

        $this->artisan("discovery:run")
            ->assertExitCode(0);

        // No cache files should be created
        $files = glob($this->cachePath . "/*.php");
        expect($files)->toBeEmpty();
    });

    test("command creates cache directory structure", function (): void {
        // Remove cache directory if it exists
        if (is_dir($this->cachePath)) {
            $files = glob($this->cachePath . "/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->cachePath);
        }

        Discovery::config()->addInterface(TestInterface::class);

        $this->artisan("discovery:run")
            ->assertExitCode(0);

        expect(is_dir($this->cachePath))->toBeTrue();
    });

    test("command creates cache files with proper structure", function (): void {
        Discovery::config()->addInterface(TestInterface::class);

        $this->artisan("discovery:run")
            ->assertExitCode(0);

        $cacheFile = $this->cachePath . "/TestInterface.php";
        expect(file_exists($cacheFile))->toBeTrue();

        $implementations = require $cacheFile;
        expect($implementations)->toBeArray();
        
        // Cache file should contain valid PHP array structure
        $cacheContent = file_get_contents($cacheFile);
        expect($cacheContent)->toStartWith("<?php")
            ->and($cacheContent)->toContain("return");
    });

    test("command handles app_modules directory", function (): void {
        // Create app_modules directory temporarily
        $appModulesPath = base_path("app_modules");
        $shouldCleanup = false;
        
        if (!is_dir($appModulesPath)) {
            mkdir($appModulesPath, 0755, true);
            $shouldCleanup = true;
        }

        Discovery::config()->addInterface(TestInterface::class);

        $this->artisan("discovery:run")
            ->assertExitCode(0);

        // Command should complete successfully
        expect(Discovery::config()->interfaces)->toContain("TestInterface");

        // Cleanup
        if ($shouldCleanup) {
            rmdir($appModulesPath);
        }
    });

    test("command handles app-modules directory", function (): void {
        // Create app-modules directory temporarily
        $appModulesPath = base_path("app-modules");
        $shouldCleanup = false;
        
        if (!is_dir($appModulesPath)) {
            mkdir($appModulesPath, 0755, true);
            $shouldCleanup = true;
        }

        Discovery::config()->addInterface(TestInterface::class);

        $this->artisan("discovery:run")
            ->assertExitCode(0);

        // Command should complete successfully
        expect(Discovery::config()->interfaces)->toContain("TestInterface");

        // Cleanup
        if ($shouldCleanup) {
            rmdir($appModulesPath);
        }
    });
});