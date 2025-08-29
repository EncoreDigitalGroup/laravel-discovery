<?php

namespace EncoreDigitalGroup\LaravelDiscovery\Support\Config;

class DiscoveryConfig {
    public string $cachePath;
    public array $vendors = [];
    public array $interfaces = [];

    public function __construct() {
        $this->cachePath = base_path("boostrap/cache/discovery");
    }

    public function addVendor(string $vendor): self
    {
        $this->vendors[] = $vendor;

        return $this;
    }

    public function addInterface(string $name): self
    {
        if(class_exists($name)){
            $name = class_basename($name);
            $this->interfaces[] = $name;
        }

        return $this;
    }
}