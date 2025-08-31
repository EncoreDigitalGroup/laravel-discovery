<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;

uses(Tests\TestCase::class)
    ->in("Feature", "Unit");

beforeAll(function (): void {
    $vendorDir = base_path("/vendor/laravel");
    if (!is_dir($vendorDir)) {
        mkdir($vendorDir, 0755, true);
    }
});

beforeEach(function (): void {
    createCacheDirectory();
});

afterEach(function (): void {
    cleanupCacheDirectory();
});

function createCacheDirectory(): void
{
    $cachePath = Discovery::config()->cachePath;
    if (!is_dir($cachePath)) {
        mkdir($cachePath, 0755, true);
    }
}

function cleanupCacheDirectory(): void
{
    $cachePath = Discovery::config()->cachePath;
    if (is_dir($cachePath)) {
        deleteDirectory($cachePath);
    }
}

function deleteDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), [".", ".."]);

    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }

    rmdir($dir);
}