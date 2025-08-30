<?php

/*
 * Copyright (c) 2025. Encore Digital Group.
 * All Rights Reserved.
 */

namespace EncoreDigitalGroup\LaravelDiscovery\Console\Commands;

use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use EncoreDigitalGroup\LaravelDiscovery\Support\InterfaceImplementorFinder;
use EncoreDigitalGroup\StdLib\Objects\Support\Types\Str;
use Illuminate\Console\Command;
use InvalidArgumentException;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/** @internal */
class DiscoverInterfaceImplementationsCommand extends Command
{
    protected $signature = "discovery:run";
    protected $description = "Generate a list of classes implementing Tenant interfaces";
    private Parser $parser;
    private NodeTraverser $traverser;

    public function handle(): void
    {
        foreach (Discovery::config()->interfaces as $interface) {
            $this->discover($interface, Discovery::config()->cachePath . "/{$interface}.php}");
        }
    }

    private function discover(string $interfaceName, string $cachePath): void
    {
        if ($interfaceName == Str::empty()) {
            throw new InvalidArgumentException("Interface Name Cannot Be Empty String");
        }

        $this->newLine();
        $this->info("Discovering {$interfaceName} Implementations.");

        $this->parser = (new ParserFactory)->createForVersion(PhpVersion::getHostVersion());
        $this->traverser = new NodeTraverser;
        $finder = new InterfaceImplementorFinder;
        $finder->setInterfaceName($interfaceName);

        $this->traverser->addVisitor($finder);

        foreach ($this->directories() as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $this->traverse($directory);
        }

        $implementingClasses = $finder->getImplementingClasses();

        $fileContent = "<?php\n\nreturn " . var_export($implementingClasses, true) . ";\n";
        $directory = dirname($cachePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($cachePath, $fileContent);

        $this->info("{$interfaceName} Discovery Complete.");
        $this->newLine();
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

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === "php") {
                $code = file_get_contents($file->getPathname());
                if (!$code) {
                    continue;
                }

                try {
                    $ast = $this->parser->parse($code);

                    if (is_null($ast)) {
                        continue;
                    }

                    $this->traverser->traverse($ast);
                } catch (Error) {
                    // Skip files with parsing errors
                }
            }
        }
    }
}