<?php

/*
 * Copyright (c) 2025. Encore Digital Group.
 * All Rights Reserved.
 */

namespace EncoreDigitalGroup\LaravelDiscovery\Support;

/** @internal */
class SystemResourceDetector
{
    public static function make(): SystemResourceProfile
    {
        return (new self())->detect();
    }

    public function detect(): SystemResourceProfile
    {
        $cpuCores = $this->detectCpuCores();
        $memoryAvailable = $this->detectAvailableMemory();

        return new SystemResourceProfile(
            cpuCores: $cpuCores,
            cpuScore: $this->calculateCpuScore($cpuCores),
            memoryAvailable: $memoryAvailable,
            memoryScore: $this->calculateMemoryScore($memoryAvailable),
            diskIoScore: $this->calculateDiskIoScore(),
            opcacheEnabled: function_exists('opcache_get_status') && opcache_get_status() !== false,
            jitEnabled: function_exists('opcache_get_status') &&
                       isset(opcache_get_status()['jit']) &&
                       opcache_get_status()['jit']['enabled'],
            parallelExtension: extension_loaded('parallel'),
            fiberSupport: class_exists('Fiber'),
        );
    }

    private function detectCpuCores(): int
    {
        // Try environment variable first (works on both Windows and Unix)
        $processors = getenv('NUMBER_OF_PROCESSORS');
        if ($processors && is_numeric($processors)) {
            return (int) $processors;
        }

        // Try shell commands only if shell_exec is available
        if (function_exists('shell_exec') && !$this->isWindowsWithoutShell()) {
            // Linux
            $output = shell_exec('nproc 2>/dev/null');
            if ($output && is_numeric(trim($output))) {
                return (int) trim($output);
            }

            // macOS
            $output = shell_exec('sysctl -n hw.ncpu 2>/dev/null');
            if ($output && is_numeric(trim($output))) {
                return (int) trim($output);
            }
        }

        return 2;
    }

    private function isWindowsWithoutShell(): bool
    {
        return PHP_OS_FAMILY === 'Windows' && !$this->hasValidShell();
    }

    private function hasValidShell(): bool
    {
        if (!function_exists('shell_exec')) {
            return false;
        }

        // Test if shell_exec works without causing path errors
        $test = @shell_exec('echo test 2>nul');
        return $test !== null && trim($test) === 'test';
    }

    private function calculateCpuScore(int $cores): float
    {
        if ($cores >= 8) {
            return 1.0;
        } elseif ($cores >= 4) {
            return 0.8;
        } elseif ($cores >= 2) {
            return 0.6;
        } else {
            return 0.3;
        }
    }

    private function detectAvailableMemory(): int
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit === '-1') {
            return 2048 * 1024 * 1024;
        }

        return $this->parseMemoryLimit($memoryLimit);
    }

    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $unit = strtoupper(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        return match ($unit) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => (int)$memoryLimit,
        };
    }

    private function calculateMemoryScore(int $availableMemory): float
    {
        $memoryInMB = $availableMemory / (1024 * 1024);

        if ($memoryInMB >= 2048) {
            return 1.0;
        } elseif ($memoryInMB >= 1024) {
            return 0.8;
        } elseif ($memoryInMB >= 512) {
            return 0.6;
        } elseif ($memoryInMB >= 256) {
            return 0.4;
        } else {
            return 0.2;
        }
    }

    private function calculateDiskIoScore(): float
    {
        $testFile = tempnam(sys_get_temp_dir(), 'discovery_io_test');
        if (!$testFile) {
            return 0.5;
        }

        file_put_contents($testFile, str_repeat('test data ', 1000));

        $startTime = microtime(true);
        file_get_contents($testFile);
        $endTime = microtime(true);

        unlink($testFile);

        $readTime = $endTime - $startTime;

        if ($readTime < 0.001) {
            return 1.0;
        } elseif ($readTime < 0.005) {
            return 0.8;
        } elseif ($readTime < 0.01) {
            return 0.6;
        } else {
            return 0.3;
        }
    }
}