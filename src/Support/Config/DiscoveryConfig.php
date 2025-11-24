<?php

namespace EncoreDigitalGroup\LaravelDiscovery\Support\Config;

use EncoreDigitalGroup\LaravelDiscovery\Support\SystemResourceDetector;
use EncoreDigitalGroup\LaravelDiscovery\Support\SystemResourceProfile;
use EncoreDigitalGroup\StdLib\Exceptions\FilesystemExceptions\DirectoryNotFoundException;
use EncoreDigitalGroup\StdLib\Objects\Support\Types\Str;
use Illuminate\Support\Facades\App;

class DiscoveryConfig
{
    public string $cachePath;
    public array $vendors = [];
    public array $interfaces = [];
    /** @deprecated Will be made private in the next major version */
    public int $concurrencyBatchSize;
    private bool $searchVendors = false;
    private bool $searchAllVendors = false;
    private ?SystemResourceProfile $resourceProfile = null;

    public function __construct()
    {
        $this->cachePath = base_path("bootstrap/cache/discovery");
        $this->resourceProfile = SystemResourceDetector::make();
        $this->concurrencyBatchSize = $this->resourceProfile->getOptimalBatchSize();
    }

    public function addVendor(string $vendor): self
    {
        $this->searchVendors();

        $vendor = Str::lower($vendor);
        $vendorPath = base_path("vendor/{$vendor}");

        if (!is_dir($vendorPath) && !App::environment("testing")) {
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

    public function setConcurrencyBatchSize(int $size): self
    {
        $this->concurrencyBatchSize = max(1, $size);

        return $this;
    }

    public function getResourceProfile(): SystemResourceProfile
    {
        if (!$this->resourceProfile instanceof \EncoreDigitalGroup\LaravelDiscovery\Support\SystemResourceProfile) {
            $this->resourceProfile = SystemResourceDetector::make();
        }

        return $this->resourceProfile;
    }
}