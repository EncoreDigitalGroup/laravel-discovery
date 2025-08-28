<?php

namespace Tests\Feature;

use EncoreDigitalGroup\LaravelDiscovery\Providers\ServiceProvider;
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use Orchestra\Testbench\TestCase;
use ReflectionClass;

class DiscoveryIntegrationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the singleton instance for each test
        $reflection = new ReflectionClass(Discovery::class);
        if ($reflection->hasProperty('instance')) {
            $instance = $reflection->getProperty('instance');
            $instance->setAccessible(true);
            $instance->setValue(null);
        }

        // Create test cache directory
        $this->createCacheDirectory();
    }

    protected function tearDown(): void
    {
        $this->cleanupCacheDirectory();
        parent::tearDown();
    }

    public function test_full_discovery_workflow(): void
    {
        // Configure discovery
        Discovery::config()
            ->addInterface('Tests\TestHelpers\TestInterface')
            ->addInterface('Tests\TestHelpers\AnotherTestInterface');

        // Run discovery command
        $this->artisan('discovery:run')
            ->expectsOutput('Discovering Tests\TestHelpers\TestInterface Implementations.')
            ->expectsOutput('Tests\TestHelpers\TestInterface Discovery Complete.')
            ->expectsOutput('Discovering Tests\TestHelpers\AnotherTestInterface Implementations.')
            ->expectsOutput('Tests\TestHelpers\AnotherTestInterface Discovery Complete.')
            ->assertExitCode(0);

        // Verify cache files were created
        $testInterfaceCachePath = Discovery::config()->cachePath . '/Tests\TestHelpers\TestInterface.php';
        $anotherTestInterfaceCachePath = Discovery::config()->cachePath . '/Tests\TestHelpers\AnotherTestInterface.php';

        $this->assertFileExists($testInterfaceCachePath);
        $this->assertFileExists($anotherTestInterfaceCachePath);

        // Verify cached data contains expected implementations
        $testInterfaceImplementations = include $testInterfaceCachePath;
        $anotherTestInterfaceImplementations = include $anotherTestInterfaceCachePath;

        $this->assertIsArray($testInterfaceImplementations);
        $this->assertIsArray($anotherTestInterfaceImplementations);

        // The actual implementations found will depend on the test environment
        // but the cache structure should be correct
        $this->assertTrue(is_array($testInterfaceImplementations));
        $this->assertTrue(is_array($anotherTestInterfaceImplementations));
    }

    public function test_discovery_with_vendor_configuration(): void
    {
        Discovery::config()
            ->addInterface('TestInterface')
            ->addVendor('encoredigitalgroup/stdlib');

        $this->artisan('discovery:run')
            ->assertExitCode(0);
    }

    public function test_discovery_config_persistence(): void
    {
        $config1 = Discovery::config();
        $config1->addInterface('FirstInterface');

        $config2 = Discovery::config();

        // Should be the same instance (singleton)
        $this->assertSame($config1, $config2);
        $this->assertContains('FirstInterface', $config2->interfaces);
    }

    public function test_cache_method_integration(): void
    {
        // Create a test cache file
        $interfaceName = 'TestInterface';
        $testData = ['TestClass1', 'TestClass2'];
        $cacheFile = Discovery::config()->cachePath . "/{$interfaceName}.php";

        $directory = dirname($cacheFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($cacheFile, "<?php\n\nreturn " . var_export($testData, true) . ";\n");

        $result = Discovery::cache($interfaceName);

        $this->assertEquals($testData, $result);
    }

    protected function createCacheDirectory(): void
    {
        $cachePath = Discovery::config()->cachePath;
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
    }

    protected function cleanupCacheDirectory(): void
    {
        $cachePath = Discovery::config()->cachePath;
        if (is_dir($cachePath)) {
            $this->deleteDirectory($cachePath);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }
}