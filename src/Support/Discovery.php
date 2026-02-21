<?php

/*
 * Copyright (c) 2025. Encore Digital Group.
 * All Rights Reserved.
 */

namespace EncoreDigitalGroup\LaravelDiscovery\Support;

use EncoreDigitalGroup\LaravelDiscovery\Support\Config\DiscoveryConfig;
use Illuminate\Support\Facades\App;
use RuntimeException;

class Discovery
{
    private static self $instance;
    private static array $mocks = [];
    private DiscoveryConfig $config;

    public function __construct()
    {
        $this->config = new DiscoveryConfig;
    }

    public static function make(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public static function config(): DiscoveryConfig
    {
        return self::make()->config;
    }

    public static function path(string $key): string
    {
        if (interface_exists($key)) {
            $key = class_basename($key);
        }

        return Discovery::config()->cachePath . "/{$key}.php";
    }

    public static function mock(string $key, array $implementations): void
    {
        if (interface_exists($key)) {
            $key = class_basename($key);
        }

        self::$mocks[$key] = $implementations;
    }

    public static function clearMocks(): void
    {
        self::$mocks = [];
    }

    public static function get(string $key): array
    {
        if (interface_exists($key)) {
            $key = class_basename($key);
        }

        if (!App::environment("production") && isset(self::$mocks[$key])) {
            return self::$mocks[$key];
        }

        return require Discovery::config()->cachePath . "/{$key}.php";
    }

    /** @deprecated use Discovery::get() instead. */
    public static function cache(string $key): array
    {
        return self::get($key);
    }

    /** @internal */
    public static function refresh(): DiscoveryConfig
    {
        if (!App::environment(["testing"])) {
            throw new RuntimeException("Discovery::refresh() can only be used in testing environments.");
        }

        self::$instance = new self;
        self::$mocks = [];

        return self::make()->config;
    }
}