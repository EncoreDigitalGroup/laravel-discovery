<?php

namespace Tests;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->enablesPackageDiscoveries = true;
        parent::setUp();

        $this->setupAppKey();
    }

    public function ignorePackageDiscoveriesFrom(): array
    {
        return [];
    }

    private function setupAppKey(): void
    {
        Config::set("app.key", "base64:" . base64_encode(Encrypter::generateKey(Config::get("app.cipher"))));
    }
}