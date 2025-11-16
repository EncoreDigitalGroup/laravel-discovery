<?php

/*
 * Copyright (c) 2025. Encore Digital Group.
 * All Rights Reserved.
 */

namespace EncoreDigitalGroup\LaravelDiscovery\Console\Commands;

use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use EncoreDigitalGroup\LaravelDiscovery\Support\InterfaceImplementorFinder;
use EncoreDigitalGroup\StdLib\Objects\Support\Types\Number;
use EncoreDigitalGroup\StdLib\Objects\Support\Types\Str;
use Fiber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;

use function Laravel\Prompts\progress;

use Laravel\Prompts\Progress;
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
    private const array EXCLUDED_DIR_PATTERNS = [
        "*/tests/*",
        "*/test/*",
        "*/Test/*",
        "*/Tests/*",
        "*/docs/*",
        "*/doc/*",
        "*/documentation/*",
        "*/examples/*",
        "*/example/*",
        "*/fixtures/*",
        "*/stubs/*",
        "*/stub/*",
        "*/resources/views/*",
        "*/resources/lang/*",
        "*/resources/css/*",
        "*/resources/js/*",
        "*/public/*",
        "*/storage/*",
        "*/node_modules/*",
        "*/assets/*",
        "*/build/*",
        "*/dist/*",
        "*/vendor/bin/*",
        "*/database/migrations/*",
        "*/database/seeds/*",
        "*/database/factories/*",
    ];

    protected $signature = "discovery:run";
    protected $description = "Generate a list of classes implementing Tenant interfaces";
    private Parser $parser;
    private NodeTraverser $traverser;

    public function handle(): void
    {
        $startedAt = Date::now();
        $this->newLine();
        $this->info("Discovering...");

        $interfaces = Discovery::config()->interfaces;

        if ($interfaces === []) {
            $this->warn("No interfaces configured for discovery.");

            return;
        }

        // Single-pass discovery: parse each file once and check all interfaces
        $this->discoverAll($interfaces);

        $duration = $startedAt->diff(Date::now());

        $this->info("Discovery completed in " . $duration->forHumans());
        $this->newLine(2);
    }

    private function discoverAll(array $interfaces): void
    {
        foreach ($interfaces as $interface) {
            if ($interface == Str::empty()) {
                throw new InvalidArgumentException("Interface Name Cannot Be Empty String");
            }
        }

        $this->info("Discovering " . Number::format(count($interfaces)) . " interface(s).");

        $this->parser = (new ParserFactory)->createForVersion(PhpVersion::getHostVersion());
        $this->traverser = new NodeTraverser;
        $finder = new InterfaceImplementorFinder;
        $finder->setInterfaceNames($interfaces);

        $this->traverser->addVisitor($finder);

        $allFiles = [];
        foreach ($this->directories() as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $allFiles = array_merge($allFiles, $this->collectFiles($directory));
        }

        $totalFiles = count($allFiles);

        if ($totalFiles === 0) {
            $this->warn("No PHP files found to scan.");
        } else {
            $this->info("Scanning " . Number::format($totalFiles) . " files...");

            $this->processFilesWithProgress($allFiles);
        }

        $cachePath = Discovery::config()->cachePath;
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        foreach ($interfaces as $interface) {
            $implementingClasses = $finder->getImplementingClassesForInterface($interface);
            $fileContent = "<?php\n\nreturn " . var_export($implementingClasses, true) . ";\n";
            file_put_contents($cachePath . "/{$interface}.php", $fileContent);
        }
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

    private function collectFiles(string $directory): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $files = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === "php") {
                if ($this->isPathExcluded($file->getPathname())) {
                    continue;
                }

                $files[] = $file;
            }
        }

        return $files;
    }

    private function isPathExcluded(string $path): bool
    {
        $normalizedPath = str_replace('\\', "/", $path);

        foreach (self::EXCLUDED_DIR_PATTERNS as $pattern) {
            if (fnmatch($pattern, $normalizedPath, FNM_PATHNAME)) {
                return true;
            }
        }

        return false;
    }

    private function processFilesWithProgress(array $files): void
    {
        $batchSize = Discovery::config()->concurrencyBatchSize;

        progress(
            label: "Processing files",
            steps: $files,
            callback: fn ($file, $progress) => $this->processFileWithProgress($file, $progress, $batchSize),
        );
    }

    private function processFileWithProgress(SplFileInfo $file, Progress $progress, int $batchSize): void
    {
        static $batch = [];
        static $totalProcessed = 0;

        $batch[] = $file;
        $totalProcessed++;

        if (count($batch) >= $batchSize || $totalProcessed === $progress->total) {
            $this->processBatchConcurrently($batch);
            $batch = [];
        }
    }

    private function processBatchConcurrently(array $files): void
    {
        $fibers = [];

        foreach ($files as $file) {
            $fibers[] = new Fiber(function () use ($file): void {
                $this->processFile($file);
            });
        }

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