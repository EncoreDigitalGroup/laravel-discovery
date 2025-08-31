<?php

use EncoreDigitalGroup\LaravelDiscovery\Console\Commands\DiscoverInterfaceImplementationsCommand;
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use EncoreDigitalGroup\StdLib\Objects\Support\Types\Str;
use Illuminate\Support\Facades\Artisan;
use Tests\TestHelpers\AnotherTestInterface;
use Tests\TestHelpers\TestInterface;

describe("DiscoveryCommand Tests", function (): void {
    test("command runs successfully with no interfaces configured", function (): void {
        $result = Artisan::call(DiscoverInterfaceImplementationsCommand::class);

        expect($result)
            ->toBe(0);
    });

    test("command handles interfaces correctly", function (): void {
        Discovery::config()
            ->addInterface(TestInterface::class)
            ->addInterface(AnotherTestInterface::class);

        $this->artisan("discovery:run")
            ->assertExitCode(0);

        expect(Discovery::config()->interfaces)
            ->toContain(TestInterface::class)
            ->and(Discovery::config()->interfaces)
            ->toContain(AnotherTestInterface::class);
    });

    test("invalid interface not added to interfaces array", function (): void {
        Discovery::config()
            ->addInterface(Str::empty())
            ->addInterface("FakeInterface");

        expect(Discovery::config()->interfaces)
            ->not()->toContain(Str::empty(), "FakeInterface");

        $this->artisan("discovery:run")
            ->assertExitCode(0);
    });

    test("vendor added to vendors array", function (): void {
        Discovery::config()
            ->addVendor("encoredigitalgroup");

        expect(Discovery::config()->vendors)
            ->toContain("encoredigitalgroup");

        $this->artisan("discovery:run")
            ->assertExitCode(0);
    });

    test("prevents duplicate vendors and interfaces", function (): void {
        Discovery::config()
            ->addVendor("encoredigitalgroup")
            ->addVendor("encoredigitalgroup");

        expect(Discovery::config()->vendors)->toEqual(["encoredigitalgroup"]);

        Discovery::config()
            ->addInterface(TestInterface::class)
            ->addInterface(TestInterface::class);

        expect(Discovery::config()->interfaces)
            ->toEqual(["TestInterface"]);
    });

    test("command has correct configuration", function (): void {
        $command = new DiscoverInterfaceImplementationsCommand;

        expect($command->getName())->toEqual("discovery:run")
            ->and($command->getDescription())->toEqual("Generate a list of classes implementing Tenant interfaces");
    });
});