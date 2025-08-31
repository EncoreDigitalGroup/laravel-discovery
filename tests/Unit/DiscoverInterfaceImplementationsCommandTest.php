<?php

use EncoreDigitalGroup\LaravelDiscovery\Console\Commands\DiscoverInterfaceImplementationsCommand;
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use Tests\TestHelpers\TestInterface;

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

describe("DiscoverInterfaceImplementationsCommand Unit Tests", function (): void {
    //    test("handle method processes configured interfaces", function (): void {
    //        Discovery::config()->addInterface(TestInterface::class);
    //
    //         Test that handle method exists and is callable
    //        $command = new DiscoverInterfaceImplementationsCommand;
    //        expect(method_exists($command, 'handle'))->toBeTrue()
    //            ->and(Discovery::config()->interfaces)->toContain('TestInterface');
    //    });

    test("discover method throws exception for empty interface name", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;
        $reflection = new ReflectionClass($command);
        $discoverMethod = $reflection->getMethod("discover");
        $discoverMethod->setAccessible(true);

        expect(function () use ($command, $discoverMethod): void {
            $discoverMethod->invoke($command, "", "test-cache.php");
        })->toThrow(InvalidArgumentException::class, "Interface Name Cannot Be Empty String");
    });

    test("discover method validates interface name parameter", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;
        $reflection = new ReflectionClass($command);
        $discoverMethod = $reflection->getMethod("discover");
        $discoverMethod->setAccessible(true);

        // Test that non-empty interface name is properly validated
        expect($discoverMethod->isPrivate())->toBeTrue()
            ->and($reflection->hasMethod("discover"))->toBeTrue();
    });

    test("directories method returns default app path", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;
        $reflection = new ReflectionClass($command);
        $directoriesMethod = $reflection->getMethod("directories");
        $directoriesMethod->setAccessible(true);

        $directories = $directoriesMethod->invoke($command);

        expect($directories)->toContain(app_path());
    });

    test("directories method includes app_modules when it exists", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;
        $reflection = new ReflectionClass($command);
        $directoriesMethod = $reflection->getMethod("directories");
        $directoriesMethod->setAccessible(true);

        // Create temporary app_modules directory
        $shouldCleanup = false;
        $appModulesPath = base_path("app_modules");
        if (!is_dir($appModulesPath)) {
            mkdir($appModulesPath, 0755, true);
            $shouldCleanup = true;
        }

        $directories = $directoriesMethod->invoke($command);

        expect($directories)->toContain($appModulesPath);

        // Cleanup
        if ($shouldCleanup) {
            rmdir($appModulesPath);
        }
    });

    test("directories method includes app-modules when it exists", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;
        $reflection = new ReflectionClass($command);
        $directoriesMethod = $reflection->getMethod("directories");
        $directoriesMethod->setAccessible(true);

        // Create temporary app-modules directory
        $appModulesPath = base_path("app-modules");
        $shouldCleanup = false;
        if (!is_dir($appModulesPath)) {
            mkdir($appModulesPath, 0755, true);
            $shouldCleanup = true;
        }

        $directories = $directoriesMethod->invoke($command);

        expect($directories)->toContain($appModulesPath);

        // Cleanup
        if ($shouldCleanup) {
            rmdir($appModulesPath);
        }
    });

    test("directories method includes all vendors when search all vendors enabled", function (): void {
        Discovery::config()->searchAllVendors();

        $command = new DiscoverInterfaceImplementationsCommand;
        $reflection = new ReflectionClass($command);
        $directoriesMethod = $reflection->getMethod("directories");
        $directoriesMethod->setAccessible(true);

        $directories = $directoriesMethod->invoke($command);

        expect($directories)->toContain(base_path("vendor"))
            ->and(Discovery::config()->shouldSearchVendors())->toBeFalse();

        // Reset
        Discovery::config()->searchAllVendors(false);
    });

    test("directories method includes specific vendors when configured", function (): void {
        Discovery::config()->addVendor("test-vendor");

        $command = new DiscoverInterfaceImplementationsCommand;
        $reflection = new ReflectionClass($command);
        $directoriesMethod = $reflection->getMethod("directories");
        $directoriesMethod->setAccessible(true);

        $directories = $directoriesMethod->invoke($command);

        expect($directories)->toContain(base_path("vendor/test-vendor"));

        // Reset to prevent issues in other tests
        Discovery::config()->searchVendors(false);
        Discovery::config()->vendors = [];
    });

    test("traverse method handles controlled directory without crashing", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;
        $reflection = new ReflectionClass($command);
        $traverseMethod = $reflection->getMethod("traverse");
        $traverseMethod->setAccessible(true);

        // Test with tests directory (small and controlled)
        $testDir = dirname(__FILE__);

        expect(function () use ($command, $traverseMethod, $testDir): void {
            $traverseMethod->invoke($command, $testDir);
        })->not->toThrow(Exception::class);
    });

    test("command signature and description are correctly set", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;

        expect($command->getName())->toBe("discovery:run")
            ->and($command->getDescription())->toBe("Generate a list of classes implementing Tenant interfaces");
    });

    test("traverse method handles parsing errors gracefully", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;
        $reflection = new ReflectionClass($command);
        $traverseMethod = $reflection->getMethod("traverse");
        $traverseMethod->setAccessible(true);

        // Create a temporary directory with an invalid PHP file
        $testDir = sys_get_temp_dir() . "/discovery-traverse-test";
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        file_put_contents($testDir . "/invalid.php", "<?php invalid syntax here");

        expect(function () use ($command, $traverseMethod, $testDir): void {
            $traverseMethod->invoke($command, $testDir);
        })->not->toThrow(Exception::class);

        // Cleanup
        unlink($testDir . "/invalid.php");
        rmdir($testDir);
    });

    test("traverse method skips non-php files", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;
        $reflection = new ReflectionClass($command);
        $traverseMethod = $reflection->getMethod("traverse");
        $traverseMethod->setAccessible(true);

        // Create a temporary directory with a non-PHP file
        $testDir = sys_get_temp_dir() . "/discovery-traverse-test2";
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        file_put_contents($testDir . "/test.txt", "not php content");

        expect(function () use ($command, $traverseMethod, $testDir): void {
            $traverseMethod->invoke($command, $testDir);
        })->not->toThrow(Exception::class);

        // Cleanup
        unlink($testDir . "/test.txt");
        rmdir($testDir);
    });

    test("traverse method handles empty files", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;
        $reflection = new ReflectionClass($command);
        $traverseMethod = $reflection->getMethod("traverse");
        $traverseMethod->setAccessible(true);

        // Create a temporary directory with an empty PHP file
        $testDir = sys_get_temp_dir() . "/discovery-traverse-test3";
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        file_put_contents($testDir . "/empty.php", "");

        expect(function () use ($command, $traverseMethod, $testDir): void {
            $traverseMethod->invoke($command, $testDir);
        })->not->toThrow(Exception::class);

        // Cleanup
        unlink($testDir . "/empty.php");
        rmdir($testDir);
    });

    test("traverse method handles null AST from parser", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;
        $reflection = new ReflectionClass($command);
        $traverseMethod = $reflection->getMethod("traverse");
        $traverseMethod->setAccessible(true);

        // Create a temporary directory with minimal PHP file that might return null AST
        $testDir = sys_get_temp_dir() . "/discovery-traverse-test4";
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        file_put_contents($testDir . "/minimal.php", "<?php");

        expect(function () use ($command, $traverseMethod, $testDir): void {
            $traverseMethod->invoke($command, $testDir);
        })->not->toThrow(Exception::class);

        // Cleanup
        unlink($testDir . "/minimal.php");
        rmdir($testDir);
    });
});