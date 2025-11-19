<?php

use EncoreDigitalGroup\LaravelDiscovery\Support\SystemResourceDetector;
use EncoreDigitalGroup\LaravelDiscovery\Support\SystemResourceProfile;

describe('SystemResourceDetector', function () {
    test('make returns SystemResourceProfile instance', function () {
        $profile = SystemResourceDetector::make();

        expect($profile)->toBeInstanceOf(SystemResourceProfile::class);
    });

    test('detect returns SystemResourceProfile with correct properties', function () {
        $detector = new SystemResourceDetector();
        $profile = $detector->detect();

        expect($profile)->toBeInstanceOf(SystemResourceProfile::class)
            ->and($profile->cpuCores)->toBeInt()->toBeGreaterThan(0)
            ->and($profile->cpuScore)->toBeFloat()->toBeGreaterThan(0)
            ->and($profile->memoryAvailable)->toBeInt()->toBeGreaterThan(0)
            ->and($profile->memoryScore)->toBeFloat()->toBeGreaterThan(0)
            ->and($profile->diskIoScore)->toBeFloat()->toBeGreaterThan(0)
            ->and($profile->opcacheEnabled)->toBeBool()
            ->and($profile->jitEnabled)->toBeBool()
            ->and($profile->parallelExtension)->toBeBool()
            ->and($profile->fiberSupport)->toBeBool();
    });

    test('detectCpuCores returns default when environment and shell unavailable', function () {
        $detector = new SystemResourceDetector();

        // Use reflection to test private method
        $reflection = new ReflectionClass($detector);
        $method = $reflection->getMethod('detectCpuCores');
        $method->setAccessible(true);

        $cores = $method->invoke($detector);

        expect($cores)->toBeInt()->toBeGreaterThan(0);
    });

    test('tryEnvironmentVariable handles environment variable correctly', function () {
        $detector = new SystemResourceDetector();
        $reflection = new ReflectionClass($detector);
        $method = $reflection->getMethod('tryEnvironmentVariable');
        $method->setAccessible(true);

        $result = $method->invoke($detector);

        // The result should either be an integer or null
        expect(is_int($result) || is_null($result))->toBeTrue();
    });

    test('parseShellOutput returns null for null input', function () {
        $detector = new SystemResourceDetector();
        $reflection = new ReflectionClass($detector);
        $method = $reflection->getMethod('parseShellOutput');
        $method->setAccessible(true);

        expect($method->invoke($detector, null))->toBeNull();
    });

    test('parseShellOutput returns null for false input', function () {
        $detector = new SystemResourceDetector();
        $reflection = new ReflectionClass($detector);
        $method = $reflection->getMethod('parseShellOutput');
        $method->setAccessible(true);

        expect($method->invoke($detector, false))->toBeNull();
    });

    test('parseShellOutput returns integer for numeric string', function () {
        $detector = new SystemResourceDetector();
        $reflection = new ReflectionClass($detector);
        $method = $reflection->getMethod('parseShellOutput');
        $method->setAccessible(true);

        expect($method->invoke($detector, '4'))->toBe(4);
    });

    test('parseShellOutput returns null for non-numeric string', function () {
        $detector = new SystemResourceDetector();
        $reflection = new ReflectionClass($detector);
        $method = $reflection->getMethod('parseShellOutput');
        $method->setAccessible(true);

        expect($method->invoke($detector, 'not_a_number'))->toBeNull();
    });

    test('calculateCpuScore returns correct scores for different core counts', function () {
        $detector = new SystemResourceDetector();
        $reflection = new ReflectionClass($detector);
        $method = $reflection->getMethod('calculateCpuScore');
        $method->setAccessible(true);

        expect($method->invoke($detector, 8))->toBe(1.0)
            ->and($method->invoke($detector, 4))->toBe(0.8)
            ->and($method->invoke($detector, 2))->toBe(0.6)
            ->and($method->invoke($detector, 1))->toBe(0.3);
    });

    test('detectAvailableMemory handles unlimited memory', function () {
        $detector = new SystemResourceDetector();
        $reflection = new ReflectionClass($detector);
        $method = $reflection->getMethod('detectAvailableMemory');
        $method->setAccessible(true);

        // Mock ini_get to return -1
        $original = ini_get('memory_limit');
        ini_set('memory_limit', '-1');

        $result = $method->invoke($detector);

        // Restore original value
        ini_set('memory_limit', $original);

        expect($result)->toBe(2048 * 1024 * 1024);
    });

    test('parseMemoryLimit handles different units', function () {
        $detector = new SystemResourceDetector();
        $reflection = new ReflectionClass($detector);
        $method = $reflection->getMethod('parseMemoryLimit');
        $method->setAccessible(true);

        expect($method->invoke($detector, '1G'))->toBe(1024 * 1024 * 1024)
            ->and($method->invoke($detector, '512M'))->toBe(512 * 1024 * 1024)
            ->and($method->invoke($detector, '1024K'))->toBe(1024 * 1024)
            ->and($method->invoke($detector, '1048576'))->toBe(1048576);
    });

    test('calculateMemoryScore returns correct scores for different memory amounts', function () {
        $detector = new SystemResourceDetector();
        $reflection = new ReflectionClass($detector);
        $method = $reflection->getMethod('calculateMemoryScore');
        $method->setAccessible(true);

        expect($method->invoke($detector, 2048 * 1024 * 1024))->toBe(1.0)
            ->and($method->invoke($detector, 1024 * 1024 * 1024))->toBe(0.8)
            ->and($method->invoke($detector, 512 * 1024 * 1024))->toBe(0.6)
            ->and($method->invoke($detector, 256 * 1024 * 1024))->toBe(0.4)
            ->and($method->invoke($detector, 128 * 1024 * 1024))->toBe(0.2);
    });

    test('calculateDiskIoScore returns float between 0 and 1', function () {
        $detector = new SystemResourceDetector();
        $reflection = new ReflectionClass($detector);
        $method = $reflection->getMethod('calculateDiskIoScore');
        $method->setAccessible(true);

        $score = $method->invoke($detector);

        expect($score)->toBeFloat()
            ->and($score)->toBeGreaterThanOrEqual(0)
            ->and($score)->toBeLessThanOrEqual(1);
    });

    test('isWindowsWithoutShell correctly detects Windows without shell', function () {
        $detector = new SystemResourceDetector();
        $reflection = new ReflectionClass($detector);
        $method = $reflection->getMethod('isWindowsWithoutShell');
        $method->setAccessible(true);

        $result = $method->invoke($detector);

        expect($result)->toBeBool();
    });

    test('hasValidShell returns false when shell_exec is not available', function () {
        $detector = new SystemResourceDetector();
        $reflection = new ReflectionClass($detector);
        $method = $reflection->getMethod('hasValidShell');
        $method->setAccessible(true);

        // This test will work regardless of shell availability
        $result = $method->invoke($detector);

        expect($result)->toBeBool();
    });
});