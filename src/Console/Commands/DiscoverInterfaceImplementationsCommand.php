<?php

/*
 * Copyright (c) 2025. Encore Digital Group.
 * All Rights Reserved.
 */

namespace EncoreDigitalGroup\LaravelDiscovery\Console\Commands;

use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use EncoreDigitalGroup\LaravelDiscovery\Support\InterfaceImplementorFinder;
use EncoreDigitalGroup\StdLib\Objects\Support\Types\Str;
use Fiber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/** @internal */
class DiscoverInterfaceImplementationsCommand extends Command
{
    protected $signature = "discovery:run";
    protected $description = "Generate a list of classes implementing Tenant interfaces";
    private Parser $parser;
    private NodeTraverser $traverser;

    public function handle(): void
    {
        $this->newLine();
        $this->info("Discovering...");

        $interfaces = Discovery::config()->interfaces;

        if (empty($interfaces)) {
            $this->warn("No interfaces configured for discovery.");
            return;
        }

        // Single-pass discovery: parse each file once and check all interfaces
        $this->discoverAll($interfaces);

        $this->info("Discovery Complete!");
        $this->newLine(2);
    }

    private function discoverAll(array $interfaces): void
    {
        foreach ($interfaces as $interface) {
            if ($interface == Str::empty()) {
                throw new InvalidArgumentException("Interface Name Cannot Be Empty String");
            }
        }

        $startedAt = Date::now();
        $this->info("Discovering " . count($interfaces) . " interface(s).");

        $this->parser = (new ParserFactory)->createForVersion(PhpVersion::getHostVersion());
        $this->traverser = new NodeTraverser;
        $finder = new InterfaceImplementorFinder;
        $finder->setInterfaceNames($interfaces);

        $this->traverser->addVisitor($finder);

        foreach ($this->directories() as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $this->traverse($directory);
        }

        // Write cache files for each interface
        $cachePath = Discovery::config()->cachePath;
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        foreach ($interfaces as $interface) {
            $implementingClasses = $finder->getImplementingClassesForInterface($interface);
            $fileContent = "<?php\n\nreturn " . var_export($implementingClasses, true) . ";\n";
            file_put_contents($cachePath . "/{$interface}.php", $fileContent);
        }
        $duration = $startedAt->diff(Date::now());

        $this->info("Discovery completed in " . $duration->forHumans());
    }

    private function directories(): array
    {
        $directories = [app_path()];

        if (is_dir(base_path("app_modules"))) {
            $directories[] = base_path("app_modules");
        }

        if (is_dir(base_path("app-modules"))) {
            $directories[] = base_path("app-modules");
        }

        if (Discovery::config()->shouldSearchAllVendors()) {
            Discovery::config()->searchVendors(false);
            $directories[] = base_path("vendor");

            return $directories;
        }

        if (Discovery::config()->shouldSearchVendors()) {
            foreach (Discovery::config()->vendors as $vendor) {
                $directories[] = base_path("vendor/{$vendor}");
            }
        }

        return $directories;
    }

    private function traverse(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $files = [];

        // Collect all PHP files first
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === "php") {
                $files[] = $file;
            }
        }

        // Process files in batches using Fibers for concurrency
        $batchSize = Discovery::config()->concurrencyBatchSize;
        $batches = array_chunk($files, $batchSize);

        foreach ($batches as $batch) {
            $this->processBatchConcurrently($batch);
        }
    }

    private function processBatchConcurrently(array $files): void
    {
        $fibers = [];

        // Create a Fiber for each file in the batch
        foreach ($files as $file) {
            $fibers[] = new Fiber(function () use ($file) {
                $this->processFile($file);
            });
        }

        // Start all fibers
        foreach ($fibers as $fiber) {
            $fiber->start();
        }
    }

    private function processFile(SplFileInfo $file): void
    {
        $code = file_get_contents($file->getPathname());
        if (!$code) {
            return;
        }

        try {
            $ast = $this->parser->parse($code);

            if (is_null($ast)) {
                return;
            }

            $this->traverser->traverse($ast);
        } catch (Error) {
            // Skip files with parsing errors
        }
    }
}