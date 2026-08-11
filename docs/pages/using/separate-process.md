# Separate-Process Execution

<figure class="logion" data-logion="AWC 62:10">
<div class="logion-text">
<blockquote>
<p>In the year of the divided tribunal, each witness crossed alone into a chamber beyond the city; the wardens returned
voice, alarm, sentence, and elapsed hour, then erased the borrowed threshold behind him.</p>
</blockquote>
<p class="logion-citation">— <cite>Acts of the Western Court 62:10</cite></p>
</div>
<img src="../images/logia/AWC-62_10.webp" alt="A lone witness crossing a luminous causeway toward a distant tribunal as wardens tend four signals" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

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

In the ordinary trait-based integration, provide explicit runtime configuration through its optional hook. The
project-owned `DocumentationCorpus` helper is described in
[Test a README and docs/](../guides/test-documentation.md#define-the-source-set).

```php
<?php

use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExamples;
use PHPUnit\Framework\TestCase;

final class DocumentationExamplesTest extends TestCase
{
    use VerifiesPhpUnitExamples;

    protected static function akashiExampleCorpus(): ExampleCorpus
    {
        return DocumentationCorpus::load();
    }

    protected static function akashiRuntimeConfiguration(): RuntimeConfiguration
    {
        return RuntimeConfiguration::forProject(dirname(__DIR__))
            ->withBootstrap('vendor/autoload.php');
    }
}
```

A separate-process directive without `RuntimeConfiguration` is rejected; Akashi never weakens requested isolation by
running the example in-process.

Projects using a custom PHPUnit method can pass the same configuration directly to the lower-level facade:

```php
<?php

use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitRuntime;

$runtime = RuntimeConfiguration::forProject(dirname(__DIR__))
    ->withBootstrap('vendor/autoload.php');

PhpUnitRuntime::assertExample($example, $runtime);
```

Use this backend for authored namespaces, closing tags or inline HTML, relocation-sensitive magic constants, direct
`exit()` or `die()`, and examples that intentionally alter process-global state. It also prevents ordinary parse errors,
runtime exceptions, signals, and nonzero exits from terminating the hosting PHPUnit process.

## Make It the Default

Projects may select child execution for every unmarked example by changing the trait's configuration hook:

```php
<?php

protected static function akashiRuntimeConfiguration(): RuntimeConfiguration
{
    return RuntimeConfiguration::forProject(dirname(__DIR__))
        ->withDefaultExecutionMode(ExecutionMode::SeparateProcess);
}
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
