![Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜 — executable documentation testing for PHP](docs/pages/images/akashi-banner.png)

# Akashi

[![Build](https://github.com/jbboehr/akashi.php/actions/workflows/ci.yml/badge.svg)](https://github.com/jbboehr/akashi.php/actions/workflows/ci.yml)
[![Built with Nix](https://img.shields.io/badge/built%20with-Nix-5277C3?logo=nixos&logoColor=white)](flake.nix)
[![License: AGPL-3.0-only WITH romic-exception](https://img.shields.io/badge/license-AGPL--3.0--only%20WITH%20romic--exception-blue.svg)](LICENSE.md)
[![AI burn](https://img.shields.io/endpoint?url=https%3A%2F%2Fgist.githubusercontent.com%2Fjbboehr%2F48eea04b7a73a84c397af8b9dc557556%2Fraw%2Fagent-badge.json&cacheSeconds=300)](https://github.com/arlegotin/agent-badge)

Akashi is a PHP project for testing examples embedded in documentation.

**Status:** Markdown discovery and extraction, the marked-example CLI, in-process and separate-process execution,
PHPUnit integration, and PHPStan verification are implemented. The API remains provisional until the Yumemi and Yumemi
Apocrypha consumer migrations pass their acceptance gates.

## Installation

Akashi requires PHP 8.2 or later. Until the first tagged release, install the development branch:

```shell
composer require --dev jbboehr/akashi:dev-master
```

Composer installs the command as:

```shell
vendor/bin/akashi
```

Extract an explicitly marked PHP fence without adding decorative output:

```shell
vendor/bin/akashi extract \
    --marker-name=akashi-example \
    docs/pages/getting-started.md \
    hello-world
```

## Documentation

See the [documentation index](docs/pages/README.md) for current project status and development notes.

## License

Akashi is licensed under `AGPL-3.0-only WITH romic-exception`. See [LICENSE.md](LICENSE.md) and the
[Romic Exception](docs/LICENSE_EXCEPTION.md). Contributions follow the terms in [CONTRIBUTING.md](CONTRIBUTING.md).
