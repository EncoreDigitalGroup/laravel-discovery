<?php

namespace EncoreDigitalGroup\LaravelDiscovery\Support\Config;

use EncoreDigitalGroup\StdLib\Exceptions\FilesystemExceptions\DirectoryNotFoundException;
use EncoreDigitalGroup\StdLib\Objects\Support\Types\Str;

class DiscoveryConfig
{
    public string $cachePath;
    public array $vendors = [];
    public array $interfaces = [];
    private bool $searchVendors = false;
    private bool $searchAllVendors = false;

    public function __construct()
    {
        $this->cachePath = base_path("bootstrap/cache/discovery");
    }

    public function addVendor(string $vendor): self
    {
        $this->searchVendors();

        $vendor = Str::lower($vendor);
        $vendorPath = base_path("vendor/{$vendor}");

        if (!is_dir($vendorPath)) {
            throw new DirectoryNotFoundException($vendorPath);
        }

        if (!in_array($vendor, $this->vendors)) {
            $this->vendors[] = $vendor;
        }

        return $this;
    }

    public function searchVendors(bool $enable = true): self
    {
        $this->searchVendors = $enable;

        return $this;
    }

    public function searchAllVendors(bool $enable = true): self
    {
        $this->searchAllVendors = $enable;

        return $this;
    }

    public function shouldSearchVendors(): bool
    {
        return $this->searchVendors;
    }

    public function shouldSearchAllVendors(): bool
    {
        return $this->searchAllVendors;
    }

    public function addInterface(string $name): self
    {
        if (interface_exists($name)) {
            $name = class_basename($name);
            
            if (!in_array($name, $this->interfaces)) {
                $this->interfaces[] = $name;
            }
        }

        return $this;
    }
}