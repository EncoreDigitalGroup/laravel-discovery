<?php

use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;

beforeEach(function (): void {
    // Note: Cannot reset singleton instance as it's typed as 'self', not nullable
    // Tests will work with the singleton pattern as intended

    // Create test cache directory
    $cachePath = Discovery::config()->cachePath;
    if (!is_dir($cachePath)) {
        mkdir($cachePath, 0755, true);
    }
});

afterEach(function (): void {
    $cachePath = Discovery::config()->cachePath;
    if (is_dir($cachePath)) {
        // Clean up cache files
        $files = glob($cachePath . "/*");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
});

test("full discovery workflow", function (): void {
    // Get initial interface count
    $initialCount = count(Discovery::config()->interfaces);

    // Configure discovery using test interfaces
    Discovery::config()
        ->addInterface(\Tests\TestHelpers\TestInterface::class)
        ->addInterface(\Tests\TestHelpers\AnotherTestInterface::class);

    // Run discovery command - interfaces get added since they exist
    $this->artisan("discovery:run")
        ->assertExitCode(0);

    // Since interfaces were added to config, they should be present
    // Check that both new interfaces are in the array (may have previous test data)
    expect(Discovery::config()->interfaces)->toContain("TestInterface");
    expect(Discovery::config()->interfaces)->toContain("AnotherTestInterface");
    expect(count(Discovery::config()->interfaces))->toBeGreaterThan($initialCount);
});

test("discovery with vendor configuration", function (): void {
    Discovery::config()
        ->addInterface(\Tests\TestHelpers\TestInterface::class)
        ->addVendor("encoredigitalgroup/stdlib");

    $this->artisan("discovery:run")
        ->assertExitCode(0);
});

test("discovery config persistence", function (): void {
    $config1 = Discovery::config();
    $config1->addInterface(\Tests\TestHelpers\TestInterface::class);

    $config2 = Discovery::config();

    // Should be the same instance (singleton)
    expect($config2)->toBe($config1);
    // Since interface_exists() returns true for test interfaces, interface gets added
    expect($config2->interfaces)->toContain("TestInterface");
});

test("cache method integration", function (): void {
    // Create a test cache file in base_path location (where Discovery::cache looks)
    $interfaceName = "TestInterface";
    $testData = ["TestClass1", "TestClass2"];
    $cacheFile = base_path("{$interfaceName}.php");

    file_put_contents($cacheFile, "<?php\n\nreturn " . var_export($testData, true) . ";\n");

    $result = Discovery::cache($interfaceName);

    expect($result)->toEqual($testData);

    // Clean up
    unlink($cacheFile);
});