<?php

namespace Tests\Unit\Providers;

use EncoreDigitalGroup\LaravelDiscovery\Console\Commands\DiscoverInterfaceImplementationsCommand;
use EncoreDigitalGroup\LaravelDiscovery\Providers\ServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;

class ServiceProviderTest extends TestCase
{
    protected ServiceProvider $serviceProvider;
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->createApplication();
        $this->serviceProvider = new ServiceProvider($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    public function test_service_provider_registers_without_error(): void
    {
        $this->serviceProvider->register();
        
        // If we get here without exception, registration worked
        $this->assertTrue(true);
    }

    public function test_service_provider_boots_and_registers_commands(): void
    {
        $this->serviceProvider->boot();
        
        // Verify command is registered
        $this->assertTrue($this->app['console']->has('discovery:run'));
    }

    public function test_discovery_command_is_available_after_boot(): void
    {
        $this->serviceProvider->boot();
        
        $command = $this->app['console']->find('discovery:run');
        
        $this->assertInstanceOf(DiscoverInterfaceImplementationsCommand::class, $command);
    }

    public function test_service_provider_can_be_instantiated(): void
    {
        $provider = new ServiceProvider($this->app);
        
        $this->assertInstanceOf(ServiceProvider::class, $provider);
    }

    public function test_commands_are_registered_in_boot_method(): void
    {
        // Mock the commands method to verify it's called with the correct array
        $serviceProviderMock = $this->getMockBuilder(ServiceProvider::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['commands'])
            ->getMock();

        $serviceProviderMock->expects($this->once())
            ->method('commands')
            ->with([DiscoverInterfaceImplementationsCommand::class]);

        $serviceProviderMock->boot();
    }

    public function test_register_method_exists_and_is_callable(): void
    {
        $this->assertTrue(method_exists($this->serviceProvider, 'register'));
        $this->assertTrue(is_callable([$this->serviceProvider, 'register']));
    }

    public function test_boot_method_exists_and_is_callable(): void
    {
        $this->assertTrue(method_exists($this->serviceProvider, 'boot'));
        $this->assertTrue(is_callable([$this->serviceProvider, 'boot']));
    }

    public function test_service_provider_extends_laravel_base_service_provider(): void
    {
        $this->assertInstanceOf(\Illuminate\Support\ServiceProvider::class, $this->serviceProvider);
    }
}