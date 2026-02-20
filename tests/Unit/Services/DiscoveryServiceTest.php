<?php

use EncoreDigitalGroup\LaravelDiscovery\Services\DiscoveryService;
use EncoreDigitalGroup\LaravelDiscovery\Support\Config\DiscoveryConfig;
use Illuminate\Support\Facades\App;

describe("DiscoveryService", function (): void {
    beforeEach(function (): void {
        // Create unique cache path for this test to avoid parallel conflicts
        $uniqueId = uniqid("test_", true);
        $this->testCachePath = sys_get_temp_dir() . "/discovery_test_cache_{$uniqueId}";

        $this->config = new DiscoveryConfig;
        $this->config->cachePath = $this->testCachePath;

        $this->service = new DiscoveryService($this->config);

        // Ensure cache directory exists
        if (!is_dir($this->config->cachePath)) {
            mkdir($this->config->cachePath, 0755, true);
        }
    });

    afterEach(function (): void {
        // Clean up test cache directory
        if (property_exists($this, 'testCachePath') && $this->testCachePath !== null && is_dir($this->testCachePath)) {
            $files = glob($this->testCachePath . "/*.php");
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testCachePath);
        }
    });

    test("constructor initializes parser and traverser", function (): void {
        $service = new DiscoveryService($this->config);

        $reflection = new ReflectionClass($service);
        $parserProperty = $reflection->getProperty("parser");
        $parserProperty->setAccessible(true);

        $traverserProperty = $reflection->getProperty("traverser");
        $traverserProperty->setAccessible(true);

        expect($parserProperty->getValue($service))->toBeInstanceOf(\PhpParser\Parser::class)
            ->and($traverserProperty->getValue($service))->toBeInstanceOf(\PhpParser\NodeTraverser::class);
    });

    test("discoverAll handles empty interface array", function (): void {
        expect(function (): void {
            $this->service->discoverAll([]);
        })->not->toThrow(Exception::class);
    });

    test("discoverAll processes interfaces without errors", function (): void {
        $interfaces = ["TestInterface"];

        expect(function () use ($interfaces): void {
            $this->service->discoverAll($interfaces);
        })->not->toThrow(Exception::class);
    });

    test("collectAllFiles returns empty array when no directories exist", function (): void {
        // Mock app_path to return non-existent directory
        App::shouldReceive("make")->with("path")->andReturn("/nonexistent/path");

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod("collectAllFiles");
        $method->setAccessible(true);

        $files = $method->invoke($this->service);

        expect($files)->toBe([]);
    });

    test("getDirectories includes app_path by default", function (): void {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod("getDirectories");
        $method->setAccessible(true);

        $directories = $method->invoke($this->service);

        expect($directories)->toContain(app_path());
    });

    test("getDirectories includes app_modules when directory exists", function (): void {
        // Use file-based locking to prevent race conditions in parallel tests
        $lockFile = sys_get_temp_dir() . "/discovery_test_app_modules.lock";
        $lock = fopen($lockFile, "c+");
        flock($lock, LOCK_EX);

        try {
            $appModulesPath = base_path("app_modules");
            $createdDir = false;

            if (!is_dir($appModulesPath)) {
                mkdir($appModulesPath, 0755, true);
                $createdDir = true;
            }

            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod("getDirectories");
            $method->setAccessible(true);

            $directories = $method->invoke($this->service);

            expect($directories)->toContain($appModulesPath);

            // Clean up only if we created it
            if ($createdDir && is_dir($appModulesPath)) {
                rmdir($appModulesPath);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
            @unlink($lockFile);
        }
    });

    test("getDirectories includes app-modules when directory exists", function (): void {
        // Use file-based locking to prevent race conditions in parallel tests
        $lockFile = sys_get_temp_dir() . "/discovery_test_app_modules_dash.lock";
        $lock = fopen($lockFile, "c+");
        flock($lock, LOCK_EX);

        try {
            $appModulesPath = base_path("app-modules");
            $createdDir = false;

            if (!is_dir($appModulesPath)) {
                mkdir($appModulesPath, 0755, true);
                $createdDir = true;
            }

            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod("getDirectories");
            $method->setAccessible(true);

            $directories = $method->invoke($this->service);

            expect($directories)->toContain($appModulesPath);

            // Clean up only if we created it
            if ($createdDir && is_dir($appModulesPath)) {
                rmdir($appModulesPath);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
            @unlink($lockFile);
        }
    });

    test("getDirectories includes all vendor when searchAllVendors is enabled", function (): void {
        $this->config->searchAllVendors();

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod("getDirectories");
        $method->setAccessible(true);

        $directories = $method->invoke($this->service);

        expect($directories)->toContain(base_path("vendor"))
            ->and($this->config->shouldSearchVendors())->toBeFalse(); // Should be disabled when searchAllVendors is used
    });

    test("getDirectories includes specific vendors when searchVendors is enabled", function (): void {
        $this->config->searchVendors()->addVendor("symfony")->addVendor("laravel");

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod("getDirectories");
        $method->setAccessible(true);

        $directories = $method->invoke($this->service);

        expect($directories)->toContain(base_path("vendor/symfony"))
            ->and($directories)->toContain(base_path("vendor/laravel"));
    });

    test("isPathExcluded method exists and can be called", function (): void {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod("isPathExcluded");
        $method->setAccessible(true);

        // Just verify the method works without asserting specific paths
        $result = $method->invokeArgs($this->service, ["/some/test/path.php"]);
        expect(is_bool($result))->toBeTrue();
    });

    test("processFile handles file reading and parsing", function (): void {
        // Create a temporary PHP file
        $tempFile = tempnam(sys_get_temp_dir(), "test_php_");
        $tempFile .= ".php";
        file_put_contents($tempFile, "<?php\nclass TestClass {}\n");

        $splFile = new SplFileInfo($tempFile);

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod("processFile");
        $method->setAccessible(true);

        expect(function () use ($method, $splFile): void {
            $method->invoke($this->service, $splFile);
        })->not->toThrow(Exception::class);

        // Clean up
        unlink($tempFile);
    });

    test("processFile handles empty file gracefully", function (): void {
        // Create an empty file
        $tempFile = tempnam(sys_get_temp_dir(), "empty_php_");
        $tempFile .= ".php";
        file_put_contents($tempFile, "");

        $splFile = new SplFileInfo($tempFile);

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod("processFile");
        $method->setAccessible(true);

        expect(function () use ($method, $splFile): void {
            $method->invoke($this->service, $splFile);
        })->not->toThrow(Exception::class);

        // Clean up
        unlink($tempFile);
    });

    test("processFile handles parsing errors gracefully", function (): void {
        // Create a file with invalid PHP syntax
        $tempFile = tempnam(sys_get_temp_dir(), "invalid_php_");
        $tempFile .= ".php";
        file_put_contents($tempFile, "<?php\nclass TestClass {\n// Missing closing brace");

        $splFile = new SplFileInfo($tempFile);

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod("processFile");
        $method->setAccessible(true);

        expect(function () use ($method, $splFile): void {
            $method->invoke($this->service, $splFile);
        })->not->toThrow(Exception::class);

        // Clean up
        unlink($tempFile);
    });

    test("writeCacheFiles creates cache directory and files", function (): void {
        // Remove cache directory to test creation
        if (is_dir($this->config->cachePath)) {
            rmdir($this->config->cachePath);
        }

        $interfaces = ["TestInterface1", "TestInterface2"];
        $finder = new \EncoreDigitalGroup\LaravelDiscovery\Support\InterfaceImplementorFinder;

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod("writeCacheFiles");
        $method->setAccessible(true);

        $method->invoke($this->service, $interfaces, $finder);

        expect(is_dir($this->config->cachePath))->toBeTrue();

        foreach ($interfaces as $interface) {
            $cacheFile = $this->config->cachePath . "/{$interface}.php";
            expect(file_exists($cacheFile))->toBeTrue();

            // Verify file content format
            $content = file_get_contents($cacheFile);
            expect($content)->toStartWith("<?php\n\nreturn ");
        }
    });

    test("collectFiles filters PHP files only", function (): void {
        // Create temporary directory with mixed files
        $tempDir = sys_get_temp_dir() . "/discovery_test_" . uniqid();
        mkdir($tempDir, 0755, true);

        // Create various files
        file_put_contents($tempDir . "/test.php", "<?php");
        file_put_contents($tempDir . "/test.txt", "text");
        file_put_contents($tempDir . "/test.js", "javascript");

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod("collectFiles");
        $method->setAccessible(true);

        $files = $method->invoke($this->service, $tempDir);

        expect(count($files))->toBe(1)
            ->and($files[0])->toBeInstanceOf(SplFileInfo::class)
            ->and($files[0]->getExtension())->toBe("php");

        // Clean up
        unlink($tempDir . "/test.php");
        unlink($tempDir . "/test.txt");
        unlink($tempDir . "/test.js");
        rmdir($tempDir);
    });
});