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

Use the suite-based integration when discovery and execution should share one project root:

```php
<?php

use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitExampleSuite;
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExampleSuite;
use jbboehr\Akashi\Source\DocumentationSource;
use PHPUnit\Framework\TestCase;

final class DocumentationExamplesTest extends TestCase
{
    use VerifiesPhpUnitExampleSuite;

    protected static function akashiExampleSuite(): PhpUnitExampleSuite
    {
        $projectRoot = dirname(__DIR__);

        return new PhpUnitExampleSuite(
            corpus: DocumentationSource::forProject($projectRoot)
                ->includeFile('README.md')
                ->load(),
            runtimeConfiguration: RuntimeConfiguration::forProject($projectRoot)
                ->withBootstrap('vendor/autoload.php'),
        );
    }
}
```

A separate-process directive without `RuntimeConfiguration` is rejected; Akashi never weakens requested isolation by
running the example in-process. The ordinary `VerifiesPhpUnitExamples` trait remains supported with separate corpus and
runtime-configuration hooks when that shape better fits a project-owned corpus helper.

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

Expected exceptions work across this boundary, including types declared only inside the child:

```php
namespace AkashiDocs\SeparateProcess;

// akashi: separate-process, expect-exception=AkashiDocs\SeparateProcess\ImportFailure
// akashi: expect-exception-message="Import rejected", expect-exception-code=73

final class ImportFailure extends \RuntimeException
{
}

throw new ImportFailure('Import rejected by the isolated example.', 73);
```

## Make It the Default

Projects may select child execution for every unmarked example in either trait's runtime configuration:

```php
<?php

protected static function akashiRuntimeConfiguration(): RuntimeConfiguration
{
    return RuntimeConfiguration::forProject(dirname(__DIR__))
        ->withDefaultExecutionMode(ExecutionMode::SeparateProcess);
}
```

An authored `skip` directive still takes precedence, followed by `compile-only`, an authored `separate-process`
directive, the configured default, and finally the in-process fallback. Compile-only selects no backend and cannot be
combined with the separate-process directive.

## Child-Process Boundary

Akashi writes a private temporary PHP file and invokes the current `PHP_BINARY` with an argument list rather than a
shell command. The child runs from the configured project root. A configured bootstrap is loaded independently for each
child through `auto_prepend_file`. Akashi enables assertion exceptions, captures stdout and stderr separately, applies a
fixed 60-second emergency timeout, and removes temporary files in `finally`.

When an exception expectation is present, a private launcher catches `Throwable` around the authored file and records
token-bound, base64-safe typed evidence in a separate private file. Stdout and stderr remain user streams and are never
parsed as the protocol. The parent verifies the exception type, message, code, and mapped source line from that
evidence. Nonzero exits take precedence over any report; after a clean child exit, malformed changed evidence is an
infrastructure failure.

A zero exit status is success, including `exit(0)`. Nonzero exits, signals, timeouts, startup failures, and cleanup
failures become typed execution failures. Where PHP reports a usable line, Akashi maps it back to the maintained
Markdown or PHPDoc source.

Separate process means failure containment, not security isolation. The child inherits the parent's environment,
filesystem and network permissions, PHP binary, and fixed Akashi INI profile. Use an operating-system sandbox or a
dedicated CI boundary for untrusted code.
