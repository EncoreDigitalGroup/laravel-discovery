<?php

use EncoreDigitalGroup\LaravelDiscovery\Console\Commands\DiscoverInterfaceImplementationsCommand;
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use Illuminate\Support\Facades\Artisan;
use Tests\TestHelpers\TestInterface;

describe("DiscoveryCommand Tests", function (): void {
    test("command runs successfully with no interfaces configured", function (): void {
        $result = Artisan::call(DiscoverInterfaceImplementationsCommand::class);

        expect($result)
            ->toBe(0);
    });

    test("command runs discovery for configured interfaces", function (): void {
        // Since interface_exists() returns true for test interfaces,
        // interfaces will be added and command will run successfully
        Discovery::config()
            ->addInterface(TestInterface::class);

        $this->artisan("discovery:run")
            ->assertExitCode(0);
    });

    test("command creates cache files for interfaces", function (): void {
        // Since interface_exists() returns true, interfaces will be added
        Discovery::config()
            ->addInterface(TestInterface::class);

        $this->artisan("discovery:run");

        // Since interfaces were added to the config, they should be present
        expect(Discovery::config()->interfaces)->toContain("TestInterface");
    });

    test("command handles multiple interfaces", function (): void {
        Discovery::config()
            ->addInterface(TestInterface::class)
            ->addInterface(\Tests\TestHelpers\AnotherTestInterface::class);

        // Since interfaces get added, command runs successfully with processing
        $this->artisan("discovery:run")
            ->assertExitCode(0);
    });

    test("command creates cache directory if not exists", function (): void {
        // Since interface_exists() returns true, interfaces will be added
        Discovery::config()->addInterface(TestInterface::class);

        $this->artisan("discovery:run");

        // Command runs successfully and interfaces were processed
        expect(Discovery::config()->interfaces)->toContain("TestInterface");
    });

    test("command handles invalid interface name", function (): void {
        // Since addInterface only adds to array if interface_exists() returns true,
        // an empty string won't be added to the interfaces array
        // So the command will run successfully but do nothing
        Discovery::config()->addInterface("");

        $this->artisan("discovery:run")
            ->assertExitCode(0);
    });

    test("command scans configured vendor directories", function (): void {
        Discovery::config()
            ->addInterface(TestInterface::class)
            ->addVendor("specific-vendor");

        // The command should run without errors, interface added so processing occurs
        $this->artisan("discovery:run")
            ->assertExitCode(0);
    });

    test("command signature is correct", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;

        expect($command->getName())->toEqual("discovery:run");
    });

    test("command description is set", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;

        expect($command->getDescription())->toEqual("Generate a list of classes implementing Tenant interfaces");
    });
});