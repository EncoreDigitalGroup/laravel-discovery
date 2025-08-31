# Laravel Discovery

A Laravel package that automatically discovers and caches interface implementations across your codebase and vendor packages.

## Features

- **Interface Implementation Discovery**: Automatically finds all classes that implement specific interfaces
- **Caching**: Generates cached files for fast runtime lookups
- **Vendor Support**: Can search through vendor packages for implementations
- **Configurable**: Flexible configuration for search paths and interfaces
- **Artisan Command**: Simple command to trigger discovery process

## Requirements

- PHP 8.3+
- Laravel 11+

## Installation

Install the package via Composer:

```bash
composer require encoredigitalgroup/laravel-discovery
```

The service provider will be automatically registered via Laravel's package auto-discovery.

Add the following to your `post-autoload-dump` script:

```bash
@php artisan discovery:run
```

This command will:

1. Search for all configured interfaces
2. Find implementing classes in your app, modules, and configured vendor paths
3. Generate cache files in `bootstrap/cache/discovery/`

## Usage

### Basic Configuration

Configure the discovery system using the `Discovery` configuration class:

```php
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;

// Add interfaces to discover
Discovery::config()->addInterface(YourInterface::class);

// Add specific vendors to search
Discovery::config()
    ->addVendor("laravel")
    ->addVendor("spatie");

// Or search all vendors (extremely slow, use with caution)
Discovery::config()->searchAllVendors();
```

### Retrieving Cached Results

Access the discovered implementations using the cache method:

```php
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;

// Get all implementations of an interface
$implementations = Discovery::cache(YourInterface::class);
```

## Configuration Options

### Search Paths

The package searches in the following directories by default:

- `app/` - Your application code
- `app_modules/` or `app-modules/` - If they exist
- Configured vendor directories (when enabled)

### Cache Location

Cache files are stored in `bootstrap/cache/discovery/` by default. Each interface gets its own cache file named after the interface (e.g., `YourInterface.php`).

## Example

```php
<?php

// Define an interface
interface PaymentGatewayInterface
{
    public function process(float $amount): bool;
}

// Implement the interface
class StripeGateway implements PaymentGatewayInterface
{
    public function process(float $amount): bool
    {
        // Implementation
    }
}

class PayPalGateway implements PaymentGatewayInterface
{
    public function process(float $amount): bool
    {
        // Implementation
    }
}

// Configure discovery
Discovery::config()->addInterface(PaymentGatewayInterface::class);

// Run discovery
// php artisan discovery:run

// Use the cached results
$gateways = Discovery::cache(PaymentGatewayInterface::class);
// Returns: ['App\\StripeGateway', 'App\\PayPalGateway']
```

## How It Works

1. **Scanning**: The package uses PHP-Parser to analyze PHP files and identify classes that implement specified interfaces
2. **Directory Traversal**: Scans the following directories:
    - `app/` - Your application code
    - `app_modules/` or `app-modules/` - Modular application code (if present)
    - Vendor directories (when configured)
3. **Caching**: Generates PHP cache files in `bootstrap/cache/discovery/` for each interface

Vendors and Interfaces are scanned/searched only once during Discovery

For example:

- `Package A` configures Discovery to search for `Interface 1`.
- `Package B` also configures Discovery to search for `Interface 1`.
- `Interface 1` will only be searched for once.
- `Interface 1` will be searched for in all locations configured by all packages.

## Testing

Run the test suite:

```bash
composer test
```

## License

This repository is licensed under a [modified BSD-3-Clause License](https://docs.encoredigitalgroup.com/LicenseTerms).

## Contributing

Contributions are governed by the Encore Digital Group [Contribution Terms](https://docs.encoredigitalgroup.com/Contributing/Terms).