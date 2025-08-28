<?php

namespace Tests\Feature\Console\Commands;

use EncoreDigitalGroup\LaravelDiscovery\Console\Commands\DiscoverInterfaceImplementationsCommand;
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class DiscoverInterfaceImplementationsCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \EncoreDigitalGroup\LaravelDiscovery\Providers\ServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset the singleton instance for each test
        $reflection = new \ReflectionClass(Discovery::class);
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
        // Clean up test cache directory
        $this->cleanupCacheDirectory();
        parent::tearDown();
    }

    public function test_command_runs_successfully_with_no_interfaces_configured(): void
    {
        $this->artisan('discovery:run')
            ->assertExitCode(0);
    }

    public function test_command_runs_discovery_for_configured_interfaces(): void
    {
        // Configure test interface
        Discovery::config()->addInterface('TestInterface');
        
        $this->artisan('discovery:run')
            ->expectsOutput('Discovering TestInterface Implementations.')
            ->expectsOutput('TestInterface Discovery Complete.')
            ->assertExitCode(0);
    }

    public function test_command_creates_cache_files_for_interfaces(): void
    {
        $interfaceName = 'TestInterface';
        Discovery::config()->addInterface($interfaceName);
        
        $this->artisan('discovery:run');
        
        $cachePath = Discovery::config()->cachePath . "/{$interfaceName}.php";
        $this->assertFileExists($cachePath);
        
        // Verify cache file contains valid PHP
        $cachedData = include $cachePath;
        $this->assertIsArray($cachedData);
    }

    public function test_command_handles_multiple_interfaces(): void
    {
        Discovery::config()
            ->addInterface('FirstInterface')
            ->addInterface('SecondInterface');
        
        $this->artisan('discovery:run')
            ->expectsOutput('Discovering FirstInterface Implementations.')
            ->expectsOutput('FirstInterface Discovery Complete.')
            ->expectsOutput('Discovering SecondInterface Implementations.')
            ->expectsOutput('SecondInterface Discovery Complete.')
            ->assertExitCode(0);
    }

    public function test_command_creates_cache_directory_if_not_exists(): void
    {
        // Remove the cache directory
        $this->cleanupCacheDirectory();
        $this->assertDirectoryDoesNotExist(Discovery::config()->cachePath);
        
        Discovery::config()->addInterface('TestInterface');
        
        $this->artisan('discovery:run');
        
        $this->assertDirectoryExists(Discovery::config()->cachePath);
    }

    public function test_command_handles_invalid_interface_name(): void
    {
        Discovery::config()->addInterface('');
        
        $this->artisan('discovery:run')
            ->assertExitCode(1);
    }

    public function test_command_scans_configured_vendor_directories(): void
    {
        Discovery::config()
            ->addInterface('TestInterface')
            ->addVendor('specific-vendor');
        
        // The command should run without errors even if vendor doesn't exist
        $this->artisan('discovery:run')
            ->assertExitCode(0);
    }

    public function test_command_signature_is_correct(): void
    {
        $command = new DiscoverInterfaceImplementationsCommand();
        
        $this->assertEquals('discovery:run', $command->getName());
    }

    public function test_command_description_is_set(): void
    {
        $command = new DiscoverInterfaceImplementationsCommand();
        
        $this->assertEquals('Generate a list of classes implementing Tenant interfaces', $command->getDescription());
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
            $files = glob($cachePath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($cachePath);
        }
    }

    protected function defineEnvironment($app)
    {
        // Override configuration for testing
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }
}