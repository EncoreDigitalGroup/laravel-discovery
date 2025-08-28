<?php

namespace Tests\Unit\Support\Config;

use EncoreDigitalGroup\LaravelDiscovery\Support\Config\DiscoveryConfig;
use PHPUnit\Framework\TestCase;

class DiscoveryConfigTest extends TestCase
{
    private DiscoveryConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock base_path function if it doesn't exist
        if (!function_exists('base_path')) {
            function base_path($path = '') {
                return '/mock/base/path' . ($path ? '/' . ltrim($path, '/') : '');
            }
        }
        
        $this->config = new DiscoveryConfig();
    }

    public function test_constructor_sets_default_cache_path(): void
    {
        $expectedPath = base_path("boostrap/cache/discovery");
        
        $this->assertEquals($expectedPath, $this->config->cachePath);
    }

    public function test_constructor_initializes_empty_vendors_array(): void
    {
        $this->assertEquals([], $this->config->vendors);
    }

    public function test_constructor_initializes_empty_interfaces_array(): void
    {
        $this->assertEquals([], $this->config->interfaces);
    }

    public function test_add_vendor_adds_vendor_to_array(): void
    {
        $vendor = 'test-vendor';
        
        $result = $this->config->addVendor($vendor);
        
        $this->assertSame($this->config, $result);
        $this->assertContains($vendor, $this->config->vendors);
    }

    public function test_add_vendor_supports_method_chaining(): void
    {
        $result = $this->config->addVendor('vendor1')->addVendor('vendor2');
        
        $this->assertSame($this->config, $result);
        $this->assertEquals(['vendor1', 'vendor2'], $this->config->vendors);
    }

    public function test_add_interface_adds_interface_to_array(): void
    {
        $interface = 'TestInterface';
        
        $result = $this->config->addInterface($interface);
        
        $this->assertSame($this->config, $result);
        $this->assertContains($interface, $this->config->interfaces);
    }

    public function test_add_interface_supports_method_chaining(): void
    {
        $result = $this->config->addInterface('Interface1')->addInterface('Interface2');
        
        $this->assertSame($this->config, $result);
        $this->assertEquals(['Interface1', 'Interface2'], $this->config->interfaces);
    }

    public function test_can_add_multiple_vendors_and_interfaces(): void
    {
        $this->config
            ->addVendor('vendor1')
            ->addVendor('vendor2')
            ->addInterface('Interface1')
            ->addInterface('Interface2');
        
        $this->assertEquals(['vendor1', 'vendor2'], $this->config->vendors);
        $this->assertEquals(['Interface1', 'Interface2'], $this->config->interfaces);
    }

    public function test_can_add_duplicate_vendors(): void
    {
        $this->config->addVendor('vendor1')->addVendor('vendor1');
        
        $this->assertEquals(['vendor1', 'vendor1'], $this->config->vendors);
    }

    public function test_can_add_duplicate_interfaces(): void
    {
        $this->config->addInterface('Interface1')->addInterface('Interface1');
        
        $this->assertEquals(['Interface1', 'Interface1'], $this->config->interfaces);
    }
}