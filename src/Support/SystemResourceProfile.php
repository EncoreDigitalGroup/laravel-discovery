<?php

/*
 * Copyright (c) 2025. Encore Digital Group.
 * All Rights Reserved.
 */

namespace EncoreDigitalGroup\LaravelDiscovery\Support;

/** @internal */
readonly class SystemResourceProfile
{
    public function __construct(
        public int $cpuCores,
        public float $cpuScore,
        public int $memoryAvailable,
        public float $memoryScore,
        public float $diskIoScore,
        public bool $opcacheEnabled,
        public bool $jitEnabled,
        public bool $parallelExtension,
        public bool $fiberSupport,
    ) {}

    public function getOptimalBatchSize(): int
    {
        $combinedScore = ($this->cpuScore + $this->memoryScore) / 2;
        if ($combinedScore >= 0.8) {
            return 2000;
        }
        if ($combinedScore >= 0.5) {
            return 1000;
        }

        if ($combinedScore >= 0.3) {
            return 500;
        } else {
            return 100;
        }
    }

    public function getOptimalConcurrency(): int
    {
        $baseConcurrency = max(1, $this->cpuCores - 1);

        if ($this->memoryScore < 0.3) {
            return max(1, intval($baseConcurrency / 2));
        }

        // macOS optimization: Use more aggressive concurrency
        if (PHP_OS_FAMILY === "Darwin") {
            return min($this->cpuCores * 2, $baseConcurrency + 4);
        }

        return $baseConcurrency;
    }

    public function shouldUseProgressiveScanning(): bool
    {
        return $this->memoryScore < 0.4;
    }
}