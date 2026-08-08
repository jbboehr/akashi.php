# Yumemi authored-namespace example

```php
<?php

namespace App\Units;

use jbboehr\Yumemi\PHPStan\UnitRegistryFactory;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_to;

final class ApplicationUnitRegistryFactory implements UnitRegistryFactory
{
    public static function create(): UnitRegistry
    {
        return UnitRegistryBuilder::default()
            ->define('shipping_pallet = 48 * inch')
            ->alias('shipping_pallets', 'shipping_pallet')
            ->build();
    }
}

$units = new Units(ApplicationUnitRegistryFactory::create());
$width = $units->quantity(2, 'shipping_pallets');

assert($width->exactDecimalValueIn('meter') === '2.4384');

$previous = Units::setDefault($units);

try {
    $nativeWidth = unit(2, 'shipping_pallets');

    assert(abs(unit_to($nativeWidth, 'shipping_pallets', 'meter') - 2.4384) < 1e-12);
} finally {
    Units::setDefault($previous);
}
```
