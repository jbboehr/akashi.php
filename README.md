![Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜 — executable documentation testing for PHP](docs/pages/images/akashi-banner.png)

# Akashi

**Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜**

Akashi is a PHP project for testing examples embedded in documentation.

**Status:** the immutable model, deterministic Markdown discovery, CommonMark PHP-fence extraction, configurable
markers, and execution directives are implemented. The extraction CLI, source transformation, execution, and
verification integrations remain under development.

## Installation

Akashi requires PHP 8.2 or later. Until the first tagged release, install the development branch:

```shell
composer require --dev jbboehr/akashi:dev-master
```

Composer installs the command as:

```shell
vendor/bin/akashi
```

## Documentation

See the [documentation index](docs/pages/README.md) for current project status and development notes.

## License

Akashi is licensed under `AGPL-3.0-only WITH romic-exception`. See [LICENSE.md](LICENSE.md) and the
[Romic Exception](docs/LICENSE_EXCEPTION.md). Contributions follow the terms in [CONTRIBUTING.md](CONTRIBUTING.md).
