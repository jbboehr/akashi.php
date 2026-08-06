{{#title Akashi - Executable documentation testing for PHP}}

![Probatio Verborum Viventium Akashi](images/akashi-banner.png)

# Akashi

**Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜**

Akashi is a PHP project for testing examples embedded in documentation.

## Status

The package now has its immutable core model, deterministic Markdown document discovery, CommonMark PHP-fence
extraction, configurable marker association, marked-example selection, execution-directive parsing, and extraction CLI.
PHP source preparation, including namespace isolation and native-assertion rewriting, and guarded in-process execution
are implemented. The named-data-set provider and PHPUnit runtime facade make that in-process path directly usable.
Separate-process runtime configuration, source preparation, and standalone process execution are implemented; PHPUnit
facade routing and PHPStan verification remain under development.

## Trust and Safety

Akashi executes trusted project documentation; its in-process executor is not a security sandbox. It rejects known
process-terminating and persistent-state constructs and restores the limited process state PHP can reverse reliably, but
it cannot contain resource exhaustion, native-extension crashes, dynamically reached `exit` or `die`, or other fatal
behavior that PHP cannot report as a `Throwable`.

Examples requiring process-level containment belong in the separate-process backend. Its executor protects the hosting
test runner, but it does not restrict the example's operating-system permissions or make untrusted code safe. Until
backend routing is added to the PHPUnit facade, separate-process directives remain unsupported through that facade and
must not be weakened to in-process execution.

## Assertion Behavior

Akashi's in-process PHPUnit integration treats native `assert()` calls as documentation-test assertions. They execute
unconditionally and do not depend on `zend.assertions`. Consequently, both the assertion expression and its description
are evaluated even in environments where PHP would otherwise compile native assertions out. Documentation examples must
not rely on assertions being production no-ops.

`PhpUnitResultAsserter` turns a successful execution into one explicit completion assertion, so examples without their
own assertions are not considered risky tests. Failures include the maintained Markdown location when available,
captured stdout and stderr, and cleanup failures; the original execution cause remains available through the exception
chain.

`PhpUnitExampleDataSets` exposes a corpus as independently named PHPUnit data sets, and `PhpUnitRuntime` runs each
example through the complete in-process transformation, execution, and reporting pipeline. A separate-process directive
is currently rejected explicitly because backend routing has not yet been implemented.

## Start Here

Continue to [Getting Started](getting-started.md) for installation and local development commands.
