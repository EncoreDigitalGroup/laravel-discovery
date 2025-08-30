<?php

/*
 * Copyright (c) 2025. Encore Digital Group.
 * All Rights Reserved.
 */

namespace EncoreDigitalGroup\LaravelDiscovery\Support;

use EncoreDigitalGroup\LaravelDiscovery\Support\Config\DiscoveryConfig;

class Discovery
{
    private static self $instance;
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

    public static function cache(string $key): array
    {
        if (class_exists($key)) {
            $key = class_basename($key);
        }

        return require base_path("{$key}.php");
    }
}