# Getting Started

Akashi targets PHP 8.2 and later.

## Installation

Until the first tagged release, install the development branch:

```shell
composer require --dev jbboehr/akashi:dev-master
```

## Verify the Command

Composer exposes the package executable at:

```shell
vendor/bin/akashi
```

The initial scaffold prints the short project name. Doctesting commands will be added as the public API is implemented.

## Development

Enter the Nix development shell, install Composer dependencies, and run the checks:

```shell
nix develop
composer install
composer cs
vendor/bin/phpstan analyse
vendor/bin/phpunit
```
