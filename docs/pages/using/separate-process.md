# Separate-Process Execution

In-process execution is Akashi's default because it is fast, shares PHPUnit's loaded project environment, and reports
through normal PHPUnit assertions. Select a child process for an example whose behavior cannot be isolated in the host.

## Choose It for One Example

Place the directive immediately before the PHP fence:

````markdown
<!-- akashi: separate-process -->

```php
exit(0);
```
````

Pass explicit runtime configuration to the PHPUnit facade:

```php
<?php

use jbboehr\Akashi\Execution\RuntimeConfiguration;

$runtime = RuntimeConfiguration::forProject(dirname(__DIR__))
    ->withBootstrap('vendor/autoload.php');

PhpUnitRuntime::assertExample($example, $runtime);
```

A separate-process directive without `RuntimeConfiguration` is rejected; Akashi never weakens requested isolation by
running the example in-process.

Use this backend for authored namespaces, closing tags or inline HTML, relocation-sensitive magic constants, direct
`exit()` or `die()`, and examples that intentionally alter process-global state. It also prevents ordinary parse errors,
runtime exceptions, signals, and nonzero exits from terminating the hosting PHPUnit process.

## Make It the Default

Projects may select child execution for every unmarked example:

```php
<?php

use jbboehr\Akashi\Execution\ExecutionMode;
use jbboehr\Akashi\Execution\RuntimeConfiguration;

$runtime = RuntimeConfiguration::forProject(dirname(__DIR__))
    ->withDefaultExecutionMode(ExecutionMode::SeparateProcess);
```

An authored `skip` directive still takes precedence, followed by an authored `separate-process` directive, the
configured default, and finally the in-process fallback.

## Child-Process Boundary

Akashi writes a private temporary PHP file and invokes the current `PHP_BINARY` with an argument list rather than a
shell command. The child runs from the configured project root. A configured bootstrap is loaded independently for each
child through `auto_prepend_file`. Akashi enables assertion exceptions, captures stdout and stderr separately, applies a
fixed 60-second emergency timeout, and removes the temporary file in `finally`.

A zero exit status is success, including `exit(0)`. Nonzero exits, signals, timeouts, startup failures, and cleanup
failures become typed execution failures. Where PHP reports a usable line, Akashi maps it back to the maintained
Markdown source.

Separate process means failure containment, not security isolation. The child inherits the parent's environment,
filesystem and network permissions, PHP binary, and fixed Akashi INI profile. Use an operating-system sandbox or a
dedicated CI boundary for untrusted code.
