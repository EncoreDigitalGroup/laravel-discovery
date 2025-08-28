# Laravel Discovery

A Laravel package that provides automatic discovery of interface implementations throughout your codebase and vendor packages.

## Features

- **Automatic Interface Discovery**: Scans your codebase to find all classes implementing specified interfaces
- **Vendor Package Support**: Optionally includes vendor packages in the discovery process
- **Caching**: Generates cached lists for improved performance
- **Artisan Command**: Simple command-line interface for running discovery
- **Configurable**: Customize which interfaces to discover and which vendor packages to include

## Installation

Install the package via Composer:

```bash
composer require encoredigitalgroup/laravel-discovery
```

The service provider will be automatically registered via Laravel's package auto-discovery.

## Usage

### Basic Discovery

Run the discovery command to scan for interface implementations. This should typically be run during composer's post-autoload-dump phase.

```bash
php artisan discovery:run
```

### Configuration

Configure which interfaces to discover by using the Discovery facade:

```php
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;

// Add interfaces to discover
Discovery::config()
    ->addInterface("YourInterface")
    ->addInterface("AnotherInterface");

// Optionally add specific vendor packages to scan
Discovery::config()->addVendor("vendor-name");
```

### Accessing Discovered Classes

Retrieved cached discovery results:

```php
use EncoreDigitalGroup\LaravelDiscovery\Support\Discovery;

// Get all classes implementing a specific interface
$implementations = Discovery::cache("YourInterface");
```

## How It Works

1. **Scanning**: The package uses PHP-Parser to analyze PHP files and identify classes that implement specified interfaces
2. **Directory Traversal**: Scans the following directories:
   - `app/` - Your application code
   - `app_modules/` or `app-modules/` - Modular application code (if present)
   - `vendor/` - By default, the entire vendor directory is scanned. If you use the `addVendor()` method in the configuration, then only the vendor
                 directories specified will be scanned.
3. **Caching**: Generates PHP cache files in `bootstrap/cache/discovery/` for each interface (this is configurable)
4. **Performance**: Cached results provide fast access to implementation lists

## Requirements

- PHP ^8.3
- Laravel ^11|^12
- nikic/php-parser ^5.4