<?php

use EncoreDigitalGroup\LaravelDiscovery\Support\Config\DiscoveryConfig;
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;

// Note: Cannot reset singleton instance as it's typed as 'self', not nullable
// Tests will work with the singleton pattern as intended

describe("Discovery", function (): void {
    test("make returns singleton instance", function (): void {
        $discovery1 = Discovery::make();
        $discovery2 = Discovery::make();

        expect($discovery1)->toBeInstanceOf(Discovery::class)
            ->and($discovery2)->toBe($discovery1);
    });

    test("config returns discovery config instance", function (): void {
        $config = Discovery::config();

        expect($config)->toBeInstanceOf(DiscoveryConfig::class);
    });

    test("config returns same instance on multiple calls", function (): void {
        $config1 = Discovery::config();
        $config2 = Discovery::config();

        expect($config2)->toBe($config1);
    });

    test("cache requires existing cache file", function (): void {
        // The cache method will throw an ErrorException when the file doesn't exist
        expect(function (): void {
            Discovery::cache("nonexistent-interface");
        })->toThrow(ErrorException::class);
    });

    test("cache returns cached data", function (): void {
        // Create a cache file in the base path
        $testData = ["TestClass1", "TestClass2"];
        $cacheFile = base_path("test-interface.php");

        file_put_contents($cacheFile, "<?php\n\nreturn " . var_export($testData, true) . ";\n");

        $result = Discovery::cache("test-interface");

        expect($result)->toEqual($testData);

        // Cleanup
        unlink($cacheFile);
    });

    test("cache method handles class_exists check correctly", function (): void {
        // Create a cache file that can be loaded by the cache method
        $testData = ["Implementation1", "Implementation2"];
        $cacheFile = base_path("TestKey.php");

        file_put_contents($cacheFile, "<?php\n\nreturn " . var_export($testData, true) . ";\n");

        // Test with a string that might be treated as a class but isn't
        $result = Discovery::cache("TestKey");

        expect($result)->toEqual($testData);

        // Cleanup
        unlink($cacheFile);
    });

    test("cache works with non-class string keys", function (): void {
        $testData = ["Implementation1", "Implementation2"];
        $cacheFile = base_path("some-interface.php");

        file_put_contents($cacheFile, "<?php\n\nreturn " . var_export($testData, true) . ";\n");

        $result = Discovery::cache("some-interface");

        expect($result)->toEqual($testData);

        // Cleanup
        unlink($cacheFile);
    });

    test("constructor creates config instance", function (): void {
        $discovery = new Discovery;

        // Test that constructor initializes config properly
        $reflection = new ReflectionClass($discovery);
        $configProperty = $reflection->getProperty("config");
        $configProperty->setAccessible(true);

        $config = $configProperty->getValue($discovery);

        expect($config)->toBeInstanceOf(DiscoveryConfig::class);
    });

    test("path returns file path for string key", function (): void {
        $path = Discovery::path("test-interface");

        expect($path)->toBe(base_path("test-interface.php"));
    });

    test("path handles class name and returns path with basename", function (): void {
        // Using DiscoveryConfig as it's a real class that exists
        $path = Discovery::path(DiscoveryConfig::class);

        expect($path)->toBe(base_path("DiscoveryConfig.php"));
    });

    test("get returns cached data for string key", function (): void {
        $testData = ["TestClass1", "TestClass2"];
        $cacheFile = base_path("test-interface-get.php");

        file_put_contents($cacheFile, "<?php\n\nreturn " . var_export($testData, true) . ";\n");

        $result = Discovery::get("test-interface-get");

        expect($result)->toEqual($testData);

        // Cleanup
        unlink($cacheFile);
    });

    test("get handles class name and returns cached data", function (): void {
        // Create a cache file using the class basename
        $testData = ["Implementation1", "Implementation2", "Implementation3"];
        $cacheFile = base_path("DiscoveryConfig.php");

        file_put_contents($cacheFile, "<?php\n\nreturn " . var_export($testData, true) . ";\n");

        // Using DiscoveryConfig as it's a real class that exists
        $result = Discovery::get(DiscoveryConfig::class);

        expect($result)->toEqual($testData);

        // Cleanup
        unlink($cacheFile);
    });

    test("get throws error when file does not exist", function (): void {
        expect(function (): void {
            Discovery::get("nonexistent-interface-get");
        })->toThrow(ErrorException::class);
    });

    test("refresh throws exception in non-testing environment", function (): void {
        // Mock the App facade to return a non-testing environment
        App::shouldReceive("environment")
            ->with(["testing"])
            ->once()
            ->andReturn(false);

        expect(function (): void {
            Discovery::refresh();
        })->toThrow(RuntimeException::class, "Discovery::refresh() can only be used in testing environments.");
    });
});
