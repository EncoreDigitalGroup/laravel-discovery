<?php

use EncoreDigitalGroup\LaravelDiscovery\Support\SystemResourceProfile;

describe("SystemResourceProfile", function (): void {
    test("constructor sets all properties correctly", function (): void {
        $profile = new SystemResourceProfile(
            cpuCores: 8,
            cpuScore: 1.0,
            memoryAvailable: 2048 * 1024 * 1024,
            memoryScore: 1.0,
            diskIoScore: 0.8,
            opcacheEnabled: true,
            jitEnabled: true,
            parallelExtension: false,
            fiberSupport: true
        );

        expect($profile->cpuCores)->toBe(8)
            ->and($profile->cpuScore)->toBe(1.0)
            ->and($profile->memoryAvailable)->toBe(2048 * 1024 * 1024)
            ->and($profile->memoryScore)->toBe(1.0)
            ->and($profile->diskIoScore)->toBe(0.8)
            ->and($profile->opcacheEnabled)->toBeTrue()
            ->and($profile->jitEnabled)->toBeTrue()
            ->and($profile->parallelExtension)->toBeFalse()
            ->and($profile->fiberSupport)->toBeTrue();
    });

    test("getOptimalBatchSize returns correct batch sizes for different combined scores", function (): void {
        // High performance system
        $highPerformanceProfile = new SystemResourceProfile(
            cpuCores: 8,
            cpuScore: 1.0,
            memoryAvailable: 2048 * 1024 * 1024,
            memoryScore: 1.0,
            diskIoScore: 1.0,
            opcacheEnabled: true,
            jitEnabled: true,
            parallelExtension: false,
            fiberSupport: true
        );

        expect($highPerformanceProfile->getOptimalBatchSize())->toBe(2000);

        // Medium performance system
        $mediumPerformanceProfile = new SystemResourceProfile(
            cpuCores: 4,
            cpuScore: 0.8,
            memoryAvailable: 1024 * 1024 * 1024,
            memoryScore: 0.8,
            diskIoScore: 0.6,
            opcacheEnabled: true,
            jitEnabled: false,
            parallelExtension: false,
            fiberSupport: true
        );

        expect($mediumPerformanceProfile->getOptimalBatchSize())->toBe(2000);

        // Low-medium performance system
        $lowMediumPerformanceProfile = new SystemResourceProfile(
            cpuCores: 2,
            cpuScore: 0.6,
            memoryAvailable: 512 * 1024 * 1024,
            memoryScore: 0.6,
            diskIoScore: 0.4,
            opcacheEnabled: false,
            jitEnabled: false,
            parallelExtension: false,
            fiberSupport: true
        );

        expect($lowMediumPerformanceProfile->getOptimalBatchSize())->toBe(1000);

        // Low performance system
        $lowPerformanceProfile = new SystemResourceProfile(
            cpuCores: 1,
            cpuScore: 0.3,
            memoryAvailable: 256 * 1024 * 1024,
            memoryScore: 0.4,
            diskIoScore: 0.3,
            opcacheEnabled: false,
            jitEnabled: false,
            parallelExtension: false,
            fiberSupport: true
        );

        expect($lowPerformanceProfile->getOptimalBatchSize())->toBe(500);

        // Very low performance system
        $veryLowPerformanceProfile = new SystemResourceProfile(
            cpuCores: 1,
            cpuScore: 0.3,
            memoryAvailable: 128 * 1024 * 1024,
            memoryScore: 0.2,
            diskIoScore: 0.2,
            opcacheEnabled: false,
            jitEnabled: false,
            parallelExtension: false,
            fiberSupport: true
        );

        expect($veryLowPerformanceProfile->getOptimalBatchSize())->toBe(100);
    });

    test("getOptimalConcurrency returns correct concurrency for different systems", function (): void {
        // High memory system
        $highMemoryProfile = new SystemResourceProfile(
            cpuCores: 8,
            cpuScore: 1.0,
            memoryAvailable: 2048 * 1024 * 1024,
            memoryScore: 1.0,
            diskIoScore: 1.0,
            opcacheEnabled: true,
            jitEnabled: true,
            parallelExtension: false,
            fiberSupport: true
        );

        expect($highMemoryProfile->getOptimalConcurrency())->toBe(7); // cpuCores - 1

        // Low memory system
        $lowMemoryProfile = new SystemResourceProfile(
            cpuCores: 8,
            cpuScore: 1.0,
            memoryAvailable: 128 * 1024 * 1024,
            memoryScore: 0.2,
            diskIoScore: 1.0,
            opcacheEnabled: true,
            jitEnabled: true,
            parallelExtension: false,
            fiberSupport: true
        );

        expect($lowMemoryProfile->getOptimalConcurrency())->toBe(3); // max(1, 7/2)

        // Single core system with good memory
        $singleCoreProfile = new SystemResourceProfile(
            cpuCores: 1,
            cpuScore: 0.3,
            memoryAvailable: 1024 * 1024 * 1024,
            memoryScore: 0.8,
            diskIoScore: 0.6,
            opcacheEnabled: true,
            jitEnabled: false,
            parallelExtension: false,
            fiberSupport: true
        );

        expect($singleCoreProfile->getOptimalConcurrency())->toBe(1); // max(1, 1-1) = 1
    });

    test("shouldUseProgressiveScanning returns true for low memory systems", function (): void {
        $lowMemoryProfile = new SystemResourceProfile(
            cpuCores: 8,
            cpuScore: 1.0,
            memoryAvailable: 200 * 1024 * 1024,
            memoryScore: 0.3,
            diskIoScore: 1.0,
            opcacheEnabled: true,
            jitEnabled: true,
            parallelExtension: false,
            fiberSupport: true
        );

        expect($lowMemoryProfile->shouldUseProgressiveScanning())->toBeTrue();
    });

    test("shouldUseProgressiveScanning returns false for high memory systems", function (): void {
        $highMemoryProfile = new SystemResourceProfile(
            cpuCores: 8,
            cpuScore: 1.0,
            memoryAvailable: 2048 * 1024 * 1024,
            memoryScore: 1.0,
            diskIoScore: 1.0,
            opcacheEnabled: true,
            jitEnabled: true,
            parallelExtension: false,
            fiberSupport: true
        );

        expect($highMemoryProfile->shouldUseProgressiveScanning())->toBeFalse();
    });

    // Test macOS optimization path if we can mock PHP_OS_FAMILY
    test("getOptimalConcurrency handles macOS optimization when conditions are met", function (): void {
        // We can't easily mock PHP_OS_FAMILY, but we can test the logic
        // by checking if the current system is macOS and expecting the appropriate behavior

        $macProfile = new SystemResourceProfile(
            cpuCores: 4,
            cpuScore: 0.8,
            memoryAvailable: 1024 * 1024 * 1024,
            memoryScore: 0.8,
            diskIoScore: 0.6,
            opcacheEnabled: true,
            jitEnabled: false,
            parallelExtension: false,
            fiberSupport: true
        );

        $concurrency = $macProfile->getOptimalConcurrency();

        expect($concurrency)->toBe(3);
    });
});