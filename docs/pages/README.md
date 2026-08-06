{{#title Akashi - Executable documentation testing for PHP}}

![Probatio Verborum Viventium Akashi](images/akashi-banner.png)

# Akashi

**Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜**

Akashi is a PHP project for testing examples embedded in documentation.

## Status

The package now has its immutable core model, deterministic Markdown document discovery, CommonMark PHP-fence
extraction, configurable marker association, marked-example selection, execution-directive parsing, and extraction CLI.
PHP source preparation, including namespace isolation and native-assertion rewriting, and guarded in-process execution
are implemented. PHPUnit reporting and verification integrations remain under development.

## Trust and Safety

Akashi executes trusted project documentation; its in-process executor is not a security sandbox. It rejects known
process-terminating and persistent-state constructs and restores the limited process state PHP can reverse reliably, but
it cannot contain resource exhaustion, native-extension crashes, dynamically reached `exit` or `die`, or other fatal
behavior that PHP cannot report as a `Throwable`.

Examples requiring process-level containment will belong in the planned separate-process backend. Until that backend is
implemented, such examples are unsupported and must not be executed in-process. Separate-process execution will protect
the hosting test runner, but it will not restrict the example's operating-system permissions or make untrusted code
safe.

## Assertion Behavior

Akashi's in-process PHPUnit integration treats native `assert()` calls as documentation-test assertions. They execute
unconditionally and do not depend on `zend.assertions`. Consequently, both the assertion expression and its description
are evaluated even in environments where PHP would otherwise compile native assertions out. Documentation examples must
not rely on assertions being production no-ops.

## Start Here

Continue to [Getting Started](getting-started.md) for installation and local development commands.
