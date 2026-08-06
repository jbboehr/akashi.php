{{#title Akashi - Executable documentation testing for PHP}}

![Probatio Verborum Viventium Akashi](images/akashi-banner.png)

# Akashi

**Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜**

Akashi is a PHP project for testing examples embedded in documentation.

## Status

The package now has its immutable core model, deterministic Markdown document discovery, CommonMark PHP-fence
extraction, configurable marker association, marked-example selection, execution-directive parsing, and extraction CLI.
PHP source preparation, including namespace isolation and native-assertion rewriting, is implemented. Execution and
verification integrations remain under development.

## Assertion Behavior

Akashi's in-process PHPUnit integration treats native `assert()` calls as documentation-test assertions. They execute
unconditionally and do not depend on `zend.assertions`. Consequently, both the assertion expression and its description
are evaluated even in environments where PHP would otherwise compile native assertions out. Documentation examples must
not rely on assertions being production no-ops.

## Start Here

Continue to [Getting Started](getting-started.md) for installation and local development commands.
