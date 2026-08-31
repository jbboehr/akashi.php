# Test a README and docs/

<figure class="logion" data-logion="RAS 47:26">
<div class="logion-text">
<blockquote>
<p>At midnight the snow upon the observatory dome rose into the air and revealed old repairs in the copper. The
astronomers beheld no star; they saw instead the patient hands that had preserved their sight, and kept vigil until the
snow descended again.</p>
</blockquote>
<p class="logion-citation">— <cite>Revelation of the Artificial Sun 47:26</cite></p>
</div>
<img src="../images/logia/RAS-47_26.webp" alt="Luminous snow rising from a copper observatory dome to reveal its old repairs" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Most projects want the root README plus a recursive documentation directory, while excluding generated books, archives,
or prose pages whose PHP fences are illustrative rather than executable.

## Define the Source Set

```php
<?php

use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Source\MarkdownSource;

final class DocumentationCorpus
{
    public static function load(): ExampleCorpus
    {
        return MarkdownSource::forProject(dirname(__DIR__))
            ->withFile('README.md')
            ->withDirectory('docs')
            ->withExcludedPath('docs/archive')
            ->withExcludedPath('docs/generated')
            ->load();
    }
}
```

All configured paths are relative to the project root. Directory includes recurse, and a directory exclusion removes its
whole subtree. Include and exclusion paths must exist when the corpus loads; a stale path is an error rather than a
silent coverage change.

`withDirectory('docs')` selects every case-sensitive `.md` file below `docs`. Each `php` fence must be intended for at
least one workflow. Mark valid PHP that should be parsed by PHPUnit without execution as `compile-only`. For fragments
that should enter no workflow, use another language label such as `php.ini` or `text`, keep the document outside this
source set, or narrow the manifest; Akashi does not yet provide a global ignore directive.

Compile-only changes PHPUnit behavior only. If the corpus also feeds PHPStan, exclude compile-only fragments with unsafe
top-level code from PHPStan selection because that workflow requires selected analysis files.

Use `DocumentationSource` instead when the same corpus should also include `.php` files containing inline PHPDoc fences
or references to canonical PHP examples. It has the same file, directory, and exclusion model and dispatches selected
files by extension.

## Use It in PHPUnit

Return the corpus from Akashi's PHPUnit trait hook:

```php
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExamples;
use PHPUnit\Framework\TestCase;

final class DocumentationExamplesTest extends TestCase
{
    use VerifiesPhpUnitExamples;

    protected static function akashiExampleCorpus(): ExampleCorpus
    {
        return DocumentationCorpus::load();
    }
}
```

Each PHP fence becomes one independently reported PHPUnit data set. A runtime `skip` directive keeps its data-set entry
visible rather than removing it from discovery. A `compile-only` example instead passes after source-aware parsing and
never executes its code.

## Keep the Set Deliberate

Prefer a short, explicit list of source roots over including the repository root. Akashi rejects duplicate physical
documents reached through overlapping includes, symbolic-link directory traversal, and documents resolving outside the
project root. Those checks keep a corpus reproducible, but they cannot decide whether an illustrative snippet is a good
test.

If several test classes need the same selection, put this source configuration in a small project-owned helper. Akashi
does not maintain a mutable global corpus registry.
