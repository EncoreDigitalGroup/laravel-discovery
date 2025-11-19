<?php

/*
 * Copyright (c) 2025. Encore Digital Group.
 * All Rights Reserved.
 */

namespace EncoreDigitalGroup\LaravelDiscovery\Services;

use EncoreDigitalGroup\LaravelDiscovery\Support\Config\DiscoveryConfig;
use EncoreDigitalGroup\LaravelDiscovery\Support\InterfaceImplementorFinder;
use EncoreDigitalGroup\LaravelDiscovery\Support\SystemResourceProfile;
use EncoreDigitalGroup\StdLib\Objects\Support\Types\Number;
use Fiber;
use Laravel\Prompts\Progress;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\warning;

/** @internal */
class DiscoveryService
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

    private Parser $parser;
    private NodeTraverser $traverser;

    public function __construct(
        private readonly DiscoveryConfig $config
    ) {
        $this->parser = (new ParserFactory)->createForVersion(PhpVersion::getHostVersion());
        $this->traverser = new NodeTraverser;
    }

    public function discoverAll(array $interfaces): void
    {
        $finder = new InterfaceImplementorFinder;
        $finder->setInterfaceNames($interfaces);
        $this->traverser->addVisitor($finder);

        $allFiles = $this->collectAllFiles();
        $totalFiles = count($allFiles);

        if ($totalFiles === 0) {
            warning("No PHP files found to scan.");
            return;
        }

        info("Scanning " . Number::format($totalFiles) . " files...");
        $this->processFiles($allFiles);
        $this->writeCacheFiles($interfaces, $finder);
    }

    private function collectAllFiles(): array
    {
        $allFiles = [];
        foreach ($this->getDirectories() as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            $allFiles = array_merge($allFiles, $this->collectFiles($directory));
        }
        return $allFiles;
    }

    private function getDirectories(): array
    {
        $directories = [app_path()];

        if (is_dir(base_path("app_modules"))) {
            $directories[] = base_path("app_modules");
        }

        if (is_dir(base_path("app-modules"))) {
            $directories[] = base_path("app-modules");
        }

        if ($this->config->shouldSearchAllVendors()) {
            $this->config->searchVendors(false);
            $directories[] = base_path("vendor");
            return $directories;
        }

        if ($this->config->shouldSearchVendors()) {
            foreach ($this->config->vendors as $vendor) {
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

    private function processFiles(array $files): void
    {
        $batchSize = $this->config->concurrencyBatchSize;
        $resourceProfile = $this->config->getResourceProfile();

        if ($this->shouldUseProgressiveProcessing($resourceProfile, $files)) {
            $this->processFilesProgressively($files, $batchSize, $resourceProfile);
        } else {
            progress(
                label: "Processing files",
                steps: $files,
                callback: fn ($file, $progress) => $this->processFileWithProgress($file, $progress, $batchSize, $resourceProfile),
                hint: "Batch Size: {$batchSize}"
            );
        }
    }

    private function shouldUseProgressiveProcessing(SystemResourceProfile $resourceProfile, array $files): bool
    {
        return PHP_OS_FAMILY === 'Windows' ||
               ($resourceProfile->shouldUseProgressiveScanning() && count($files) > 5000);
    }

    private function processFileWithProgress(SplFileInfo $file, Progress $progress, int $batchSize, SystemResourceProfile $resourceProfile): void
    {
        static $batch = [];
        static $totalProcessed = 0;

        $batch[] = $file;
        $totalProcessed++;

        if (count($batch) >= $batchSize || $totalProcessed === $progress->total) {
            $this->processBatchConcurrently($batch, $resourceProfile);
            $batch = [];

            if ($resourceProfile->memoryScore < 0.5 && $totalProcessed % ($batchSize * 2) === 0) {
                gc_collect_cycles();
            }
        }
    }

    private function processBatchConcurrently(array $files, SystemResourceProfile $resourceProfile): void
    {
        $optimalConcurrency = $resourceProfile->getOptimalConcurrency();
        $chunks = array_chunk($files, max(1, intval(count($files) / $optimalConcurrency)));

        foreach ($chunks as $chunk) {
            $fibers = [];

            foreach ($chunk as $file) {
                $fibers[] = new Fiber(function () use ($file): void {
                    $this->processFile($file);
                });
            }

            foreach ($fibers as $fiber) {
                $fiber->start();
            }
        }
    }

    private function processFilesProgressively(array $files, int $batchSize, SystemResourceProfile $resourceProfile): void
    {
        $batches = array_chunk($files, max(1, $batchSize));

        info("Using progressive scanning mode (Batch Size: {$batchSize})");

        foreach ($batches as $batch) {
            $this->processBatchConcurrently($batch, $resourceProfile);
            gc_collect_cycles();
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

    private function writeCacheFiles(array $interfaces, InterfaceImplementorFinder $finder): void
    {
        $cachePath = $this->config->cachePath;
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        foreach ($interfaces as $interface) {
            $implementingClasses = $finder->getImplementingClassesForInterface($interface);
            $fileContent = "<?php\n\nreturn " . var_export($implementingClasses, true) . ";\n";
            file_put_contents($cachePath . "/{$interface}.php", $fileContent);
        }
    }
}