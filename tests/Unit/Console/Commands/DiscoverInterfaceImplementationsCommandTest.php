<?php

use EncoreDigitalGroup\LaravelDiscovery\Console\Commands\DiscoverInterfaceImplementationsCommand;
use EncoreDigitalGroup\LaravelDiscovery\Services\DiscoveryService;
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;
use Tests\TestHelpers\TestInterface;

beforeEach(function (): void {
    $this->cachePath = Discovery::config()->cachePath;
    if (!is_dir($this->cachePath)) {
        mkdir($this->cachePath, 0755, true);
    }

    // Mock base_path function if it doesn't exist
    if (!function_exists("base_path")) {
        function base_path($path = ""): string
        {
            return "/mock/base/path" . ($path ? "/" . ltrim($path, "/") : "");
        }
    }

    $vendorDir = base_path("/vendor/laravel");
    if (!is_dir($vendorDir)) {
        mkdir($vendorDir, 0755, true);
    }
});

afterEach(function (): void {
    if (is_dir($this->cachePath)) {
        $files = glob($this->cachePath . "/*");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
});

describe("DiscoverInterfaceImplementationsCommand", function (): void {
    test("command signature and description are correctly set", function (): void {
        $discoveryService = $this->mock(DiscoveryService::class);
        $command = new DiscoverInterfaceImplementationsCommand($discoveryService);

        expect($command->getName())->toBe("discovery:run")
            ->and($command->getDescription())->toBe("Generate a list of classes implementing interfaces");
    });

    test("command runs successfully with no interfaces configured", function (): void {
        Discovery::config()->interfaces = [];

        $this->artisan("discovery:run")
            ->assertExitCode(0);
    });

    test("command runs successfully with configured interfaces", function (): void {
        Discovery::config()
            ->addInterface(TestInterface::class);

        // Create a test implementation in app directory so discovery can find it
        $appPath = app_path();
        if (!is_dir($appPath)) {
            mkdir($appPath, 0755, true);
        }

        $testImplFile = $appPath . '/TestImplementation.php';
        file_put_contents($testImplFile, '<?php

namespace App;

use Tests\TestHelpers\TestInterface;

class TestImplementation implements TestInterface
{
    public function testMethod(): void
    {
        // Test implementation
    }
}
');

        $this->artisan("discovery:run")
            ->assertExitCode(0);

        // Verify cache files were created
        $testCacheFile = $this->cachePath . "/TestInterface.php";
        expect(file_exists($testCacheFile))->toBeTrue();

        // Verify cache files contain arrays
        $testCache = require $testCacheFile;
        expect($testCache)->toBeArray()
            // Should contain the test implementation we created
            ->and($testCache)->toContain('App\TestImplementation');

        // Cleanup
        unlink($testImplFile);
    });

    test("command creates cache directory structure", function (): void {
        // Remove cache directory if it exists
        if (is_dir($this->cachePath)) {
            $files = glob($this->cachePath . "/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->cachePath);
        }

        Discovery::config()->addInterface(TestInterface::class);

        // Create a test implementation so cache files are created
        $appPath = app_path();
        if (!is_dir($appPath)) {
            mkdir($appPath, 0755, true);
        }

        $testImplFile = $appPath . '/TestImplementation.php';
        file_put_contents($testImplFile, '<?php
namespace App;
use Tests\TestHelpers\TestInterface;
class TestImplementation implements TestInterface {
    public function testMethod(): void {}
}');

        $this->artisan("discovery:run")
            ->assertExitCode(0);

        expect(is_dir($this->cachePath))->toBeTrue();

        // Cleanup
        unlink($testImplFile);
    });

    test("command creates cache files with proper structure", function (): void {
        Discovery::config()->addInterface(TestInterface::class);

        // Create a test implementation so cache files are created
        $appPath = app_path();
        if (!is_dir($appPath)) {
            mkdir($appPath, 0755, true);
        }

        $testImplFile = $appPath . '/TestImplementation.php';
        file_put_contents($testImplFile, '<?php
namespace App;
use Tests\TestHelpers\TestInterface;
class TestImplementation implements TestInterface {
    public function testMethod(): void {}
}');

        $this->artisan("discovery:run")
            ->assertExitCode(0);

        $cacheFile = $this->cachePath . "/TestInterface.php";
        expect(file_exists($cacheFile))->toBeTrue();

        $implementations = require $cacheFile;
        expect($implementations)->toBeArray();

        // Cache file should contain valid PHP array structure
        $cacheContent = file_get_contents($cacheFile);
        expect($cacheContent)->toStartWith("<?php")
            ->and($cacheContent)->toContain("return");

        // Cleanup
        unlink($testImplFile);
    });

    test("command handles empty interface name validation", function (): void {
        // Directly add an empty string to interfaces array to test validation
        Discovery::config()->interfaces = [""];

        $this->artisan("discovery:run")
            ->assertExitCode(1);
    });
});