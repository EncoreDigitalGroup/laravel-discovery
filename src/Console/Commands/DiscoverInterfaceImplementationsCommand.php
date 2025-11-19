<?php

/*
 * Copyright (c) 2025. Encore Digital Group.
 * All Rights Reserved.
 */

namespace EncoreDigitalGroup\LaravelDiscovery\Console\Commands;

use EncoreDigitalGroup\LaravelDiscovery\Services\DiscoveryService;
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use EncoreDigitalGroup\StdLib\Objects\Support\Types\Number;
use EncoreDigitalGroup\StdLib\Objects\Support\Types\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;

/** @internal */
class DiscoverInterfaceImplementationsCommand extends Command
{
    protected $signature = "discovery:run";
    protected $description = "Generate a list of classes implementing interfaces";

    public function __construct(
        private readonly DiscoveryService $discoveryService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $startedAt = Date::now();
        $this->newLine();
        $this->info("Discovering...");

        $interfaces = Discovery::config()->interfaces;

        if ($interfaces === []) {
            $this->warn("No interfaces configured for discovery.");
            return 0;
        }

        foreach ($interfaces as $interface) {
            if ($interface == Str::empty()) {
                $this->error("Interface Name Cannot Be Empty String");
                return 1;
            }
        }

        $this->info("Discovering " . Number::format(count($interfaces)) . " interface(s).");

        $this->discoveryService->discoverAll($interfaces);

        $duration = $startedAt->diff(Date::now());
        $this->info("Discovery completed in " . $duration->forHumans());
        $this->newLine(2);

        return 0;
    }
}