<?php

namespace Tests\Unit\Support;

use EncoreDigitalGroup\LaravelDiscovery\Support\Config\DiscoveryConfig;
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use PHPUnit\Framework\TestCase;

class DiscoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset the singleton instance for each test
        $reflection = new \ReflectionClass(Discovery::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);
    }

    public function test_make_returns_singleton_instance(): void
    {
        $discovery1 = Discovery::make();
        $discovery2 = Discovery::make();

        $this->assertInstanceOf(Discovery::class, $discovery1);
        $this->assertSame($discovery1, $discovery2);
    }

    public function test_config_returns_discovery_config_instance(): void
    {
        $config = Discovery::config();

        $this->assertInstanceOf(DiscoveryConfig::class, $config);
    }

    public function test_config_returns_same_instance_on_multiple_calls(): void
    {
        $config1 = Discovery::config();
        $config2 = Discovery::config();

        $this->assertSame($config1, $config2);
    }

    public function test_cache_requires_existing_cache_file(): void
    {
        $this->expectException(\Error::class);
        
        Discovery::cache('nonexistent-interface');
    }

    public function test_cache_returns_cached_data(): void
    {
        // Create a temporary cache file
        $tempDir = sys_get_temp_dir();
        $cacheFile = $tempDir . '/test-interface.php';
        
        $testData = ['TestClass1', 'TestClass2'];
        file_put_contents($cacheFile, "<?php\n\nreturn " . var_export($testData, true) . ";\n");

        // Mock base_path function
        if (!function_exists('base_path')) {
            function base_path($path = '') {
                global $tempDir;
                return $tempDir . ($path ? '/' . ltrim($path, '/') : '');
            }
        }

        $result = Discovery::cache('test-interface');

        $this->assertEquals($testData, $result);
        
        // Cleanup
        unlink($cacheFile);
    }
}