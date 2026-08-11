# Extracting Named Examples

<figure class="logion" data-logion="SFA 48:40">
<div class="logion-text">
<blockquote>
<p>A white horse returned each spring to the abandoned mill and waited beside the motionless wheel. In the twelfth year,
a child tied no bridle upon it but cleared the channel. Water arrived before noon, and the horse departed while grain
still fell warm from the stones.</p>
</blockquote>
<p class="logion-citation">— <cite>Scholia of the Fifth Archive 48:40</cite></p>
</div>
<img src="../images/logia/SFA-48_40.webp" alt="A child clearing a luminous mill channel while a white horse departs and warm grain falls" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Sometimes a documentation example should also become a real consumer fixture. Akashi can extract one stable named
example without making a second copy the source of truth. Extraction does not execute or transform the PHP.

## Mark the Example

Choose a marker name, configure it when loading the corpus, and place the matching comment before a PHP fence:

````markdown
<!-- akashi-example: greeting -->

```php
<?php

echo "Hello from Akashi!\n";
```
````

`akashi-example` is an Akashi-generic convention, not a hard-coded name. A project retaining an existing marker such as
`yumemi-example` supplies that name explicitly.

The same marker can live inside PHPDoc and applies only to a fence in that comment:

````php
/**
 * <!-- akashi-example: greeting -->
 *
 * ```php
 * echo "Hello from Akashi!\n";
 * ```
 */
````

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

use jbboehr\Akashi\Source\DocumentationSource;
use jbboehr\Akashi\Source\MarkedExampleSelector;

$corpus = DocumentationSource::forProject(dirname(__DIR__))
    ->includeFile('docs/examples.md')
    ->withMarkerName('akashi-example')
    ->load();

$example = (new MarkedExampleSelector())->select($corpus, 'greeting');
```

Use ordinary corpus loading for PHPUnit and PHPStan. A marker adds a stable author-assigned identity; it does not filter
unmarked examples from either workflow.
