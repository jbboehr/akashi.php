# Extracting Named Examples

Sometimes a documentation example should also become a real consumer fixture. Akashi can extract one stable named
example without making a second copy the source of truth. Extraction does not execute or transform the PHP.

## Mark the Example

Choose a marker name, configure it when loading the corpus, and place the matching comment before a PHP fence:

<!-- akashi-example: greeting -->

```php
<?php

echo "Hello from Akashi!\n";
```

`akashi-example` is an Akashi-generic convention, not a hard-coded name. A project retaining an existing marker such as
`yumemi-example` supplies that name explicitly.

## Extract It

```console
vendor/bin/akashi extract \
    --marker-name=akashi-example \
    docs/examples.md \
    greeting
```

On success, stdout contains only the authored PHP source with one final LF. This makes shell redirection and
byte-for-byte consumer fixtures predictable. Diagnostics go to stderr.

Markers use lowercase kebab-case and must be unique across the loaded corpus. Invalid, missing, duplicate, orphaned, or
non-PHP markers fail explicitly. See the [CLI reference](../reference/cli.md) for the complete stream and exit-status
contract.

## Select It in PHP

The same operation is available without the CLI:

```php
<?php

use jbboehr\Akashi\Source\MarkedExampleSelector;
use jbboehr\Akashi\Source\MarkdownSource;

$corpus = MarkdownSource::forProject(dirname(__DIR__))
    ->includeFile('docs/examples.md')
    ->withMarkerName('akashi-example')
    ->load();

$example = (new MarkedExampleSelector())->select($corpus, 'greeting');
```

Use ordinary corpus loading for PHPUnit and PHPStan. A marker adds a stable author-assigned identity; it does not filter
unmarked examples from either workflow.
