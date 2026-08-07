# Runtime and PHPUnit

Akashi exposes runtime documentation examples as named PHPUnit data sets and routes each example through an in-process
or separate-process backend. Both backends report through the same PHPUnit assertion facade.

## PHPUnit Composition

`PhpUnitExampleDataSets::fromCorpus()` yields one `Example` argument under each human-readable example label. It rejects
duplicate labels before yielding the first data set. A test then calls:

```php
PhpUnitRuntime::assertExample($example, $runtimeConfiguration);
```

See [Getting Started](../getting-started.md#run-examples-with-phpunit) for the complete data-provider example.

The selected mode follows this precedence:

1. An authored `<!-- akashi: separate-process -->` directive.
2. `RuntimeConfiguration::withDefaultExecutionMode()`.
3. In-process execution.

Separate-process execution always requires `RuntimeConfiguration` with an explicit project root. Akashi rejects the call
rather than weakening requested isolation. In-process execution may omit configuration, although supplying it gives the
example a deliberate working directory and optional bootstrap.

## Runtime Configuration

```php
<?php

use jbboehr\Akashi\Execution\ExecutionMode;
use jbboehr\Akashi\Execution\RuntimeConfiguration;

$configuration = RuntimeConfiguration::forProject(dirname(__DIR__))
    ->withBootstrap('vendor/autoload.php')
    ->withDefaultExecutionMode(ExecutionMode::SeparateProcess);
```

The project root is canonicalized when configured. A bootstrap must be a readable file that resolves inside that root.
Configuration is immutable.

An in-process bootstrap is loaded with `require_once`, so persistent setup such as autoloaders and declarations remains
available after the first example. Akashi restores reversible changes that the bootstrap makes to the working directory,
error-reporting level, and output-buffer stack; those top-level side effects are therefore not re-established for later
examples. A separate-process bootstrap is loaded independently in every child through `auto_prepend_file`.

## Backend Behavior

| Behavior                                   | In process                                                 | Separate process                       |
| ------------------------------------------ | ---------------------------------------------------------- | -------------------------------------- |
| Host runner survives ordinary PHP failures | Yes, when PHP reports a `Throwable`                        | Yes                                    |
| Authored namespaces                        | Rejected                                                   | Supported                              |
| Closing tags and inline HTML               | Rejected                                                   | Supported                              |
| Native `assert()`                          | Rewritten to a PHPUnit assertion                           | Enabled in the child PHP configuration |
| Working directory                          | Configured project root, or the caller's current directory | Configured project root                |
| Bootstrap                                  | Optional, loaded once per PHPUnit process                  | Optional, loaded for each child        |
| Timeout                                    | None                                                       | Fixed 60-second emergency ceiling      |
| Operating-system sandbox                   | No                                                         | No                                     |

### In-process execution

Akashi parses and resolves the example, rewrites supported native assertions, isolates declarations in a generated
namespace, and evaluates it in an empty local variable scope. Native assertions execute unconditionally, including their
arguments and descriptions, even when PHP would compile ordinary assertions out with `zend.assertions=-1`.

The safety validator rejects constructs that cannot be restored soundly, including direct process termination, authored
namespaces, global-variable statements, writes through `$GLOBALS` or superglobals, persistent handler, environment,
locale, INI, autoloader, and shutdown mutations, and ambiguous string reflection involving local declarations. Each
rejection reports the maintained Markdown location and recommends separate-process execution where appropriate.

The state guard captures example output and attempts to restore output-buffer depth, the working directory, and
`error_reporting()`. Cleanup always runs. A cleanup failure cannot turn a failed example into success or erase the
original failure.

In-process execution is best-effort isolation for trusted examples, not a security or fatal-error sandbox. Resource
exhaustion, native crashes, and dynamically reached `exit` or `die` can still terminate the PHPUnit process.

### Separate-process execution

Akashi writes the prepared example to a private temporary file and invokes the current `PHP_BINARY` with an argument
list, never a shell command. It enables assertion exceptions, sends displayed PHP diagnostics to stderr, captures stdout
and stderr separately, and removes its temporary file in `finally`.

A zero exit status is success, including an authored `exit(0)`. Nonzero exit, signal termination, timeout, startup
failure, and cleanup failure are typed execution failures. Parse and runtime line numbers are mapped back to the
maintained Markdown source where PHP provides a usable location.

The child inherits the parent's environment and operating-system permissions. Use an external sandbox or CI boundary if
the example is not trusted.

## PHPUnit Reporting

`PhpUnitResultAsserter` reports the example ID, label, maintained Markdown location, failure phase, original cause,
captured streams, and cleanup failures. A successful example records a completion assertion, so an example without an
authored assertion is not marked risky. Rewritten in-process assertions also count normally in PHPUnit.
