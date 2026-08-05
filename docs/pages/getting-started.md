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

With no arguments or `--help`, the command prints its supported invocation. `--version` reports the installed Composer
package version.

The source for the following fence places `<!-- akashi-example: hello-world -->` immediately before it:

<!-- akashi-example: hello-world -->

```php
<?php

echo "Hello from Akashi!\n";
```

Write that explicitly marked PHP fence to stdout with:

```shell
vendor/bin/akashi extract \
    --marker-name=akashi-example \
    docs/pages/getting-started.md \
    hello-world
```

The marker name is explicit so the generic command is not tied to Yumemi's comment convention. A successful extraction
writes only PHP source to stdout and preserves the opening tag. Diagnostics use stderr. Exit status `1` means a document
or extraction failure, `2` means invalid command usage, and `70` means an unexpected internal failure.

## Development

Enter the Nix development shell, install Composer dependencies, and run the checks:

```shell
nix develop
composer install
composer cs
vendor/bin/phpstan analyse
vendor/bin/phpunit
```
