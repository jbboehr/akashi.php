# Yumemi authored-namespace PHPStan example

```php
<?php

namespace App\PHPStan;

use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\PHPStan\UnitRegistryFactory;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;

final class DocumentationRegistryFactory implements UnitRegistryFactory
{
    public static function create(): UnitRegistry
    {
        return UnitRegistryBuilder::default()
            ->baseUnit('USD', Dimension::CURRENCY)
            ->define('EUR = 100 / 107 * USD')
            ->define('widget = 12 * meter')
            ->alias('widgets', 'widget')
            ->build();
    }
}
```
