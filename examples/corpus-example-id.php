<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// akashi-region: construction
$corpusId = new \jbboehr\Akashi\Model\CorpusExampleId('phpdoc-example');

assert($corpusId->value === 'phpdoc-example');
// akashi-region-end: construction
