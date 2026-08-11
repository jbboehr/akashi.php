<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// akashi-region: construction
$id = new \jbboehr\Akashi\Model\ExampleId('phpdoc-example');

assert($id->value === 'phpdoc-example');
// akashi-region-end: construction
