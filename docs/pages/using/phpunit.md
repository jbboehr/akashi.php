# PHPUnit

PHPUnit is Akashi's normal runtime integration. A data provider exposes each documentation example as an independently
named test case, and `PhpUnitRuntime` selects the runtime backend and reports its result.

## Connect a Corpus

The [Quick Start](../quick-start.md) contains the smallest complete test class. The two integration calls are:

```php
yield from PhpUnitExampleDataSets::fromCorpus($corpus);
```

and, in the data-driven test method:

```php
PhpUnitRuntime::assertExample($example);
```

Calling `assertExample()` without runtime configuration selects in-process execution. Pass a `RuntimeConfiguration` when
examples need an explicit project working directory, a bootstrap, or child-process execution:

```php
<?php

use jbboehr\Akashi\Execution\RuntimeConfiguration;

$runtime = RuntimeConfiguration::forProject(dirname(__DIR__))
    ->withBootstrap('vendor/autoload.php');

PhpUnitRuntime::assertExample($example, $runtime);
```

The runtime configuration is immutable. Its project root is canonicalized immediately, and its bootstrap must be a
readable file that resolves inside that root.

## What In-Process Execution Does

For the default path, Akashi:

1. parses the example as PHP and retains its documentation line mapping;
2. rejects syntax that cannot be isolated soundly in the hosting process;
3. rewrites supported native assertions;
4. places declarations in a generated namespace;
5. evaluates the code with an empty local variable scope;
6. captures output and restores the working directory, error-reporting level, and output-buffer depth;
7. reports the result through PHPUnit.

The example can normally use the Composer autoloader already loaded by PHPUnit. An explicitly configured in-process
bootstrap is loaded with `require_once`, once per PHPUnit process. Use it for persistent setup such as autoloaders and
declarations. Akashi restores its reversible changes to the working directory, error-reporting level, and output-buffer
stack, so a bootstrap should not rely on those top-level changes remaining in effect for later examples.

## Assertion Behavior

PHP may compile native `assert()` calls out when `zend.assertions=-1`. Documentation tests must not disappear with host
configuration, so Akashi rewrites calls that resolve to PHP's native `assert()` into unconditional PHPUnit assertions.

Supported calls provide one assertion value and at most one string, `Throwable`, or null description. Positional and
valid named arguments are accepted:

```php
assert($value > 0);
assert($value > 0, 'The value must be positive.');
assert(assertion: $value > 0, description: 'The value must be positive.');
```

Unsupported argument names, missing or duplicated assertion values, more than two arguments, argument unpacking, and
first-class-callable syntax are rejected with the documentation location. Non-native functions or methods named `assert`
are left alone.

The rewritten call always evaluates both its assertion and description. That differs from a native assertion compiled
out by PHP, so examples must not rely on either argument being a production no-op. A false assertion with a `Throwable`
description throws that object; a string becomes the PHPUnit failure message; otherwise Akashi reports the original
expression and source line.

Separate-process examples are not rewritten. The child PHP process enables native assertion exceptions explicitly.

## Skips and Failures

An authored `<!-- akashi: skip -->` directive remains a named data set, but PHPUnit reports it as skipped before Akashi
configures, transforms, bootstraps, or executes the example. It does not remove the example from PHPStan or extraction.

Successful examples record one completion assertion even when they contain no native assertion. Failures report the
example ID, label, maintained Markdown location when available, failure phase, cause, captured stdout and stderr, and
cleanup problems. The original exception remains in the exception chain.

In-process execution is trusted-code isolation, not a sandbox. Read
[Compatibility and Safety](../reference/compatibility.md) before running generated, third-party, or otherwise untrusted
documentation.
