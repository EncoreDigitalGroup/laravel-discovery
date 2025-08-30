<?php

namespace EncoreDigitalGroup\LaravelDiscovery\Support\Config;

class DiscoveryConfig
{
    public string $cachePath;
    public array $vendors = [];
    public array $interfaces = [];
    private bool $searchVendors = false;
    private bool $searchAllVendors = false;

    public function __construct()
    {
        $this->cachePath = base_path("boostrap/cache/discovery");
    }

    public function addVendor(string $vendor): self
    {
        $this->searchVendors();
        $this->vendors[] = $vendor;

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
            $this->interfaces[] = $name;
        }

        return $this;
    }
}