# PHPUnit 10 compatibility fixture

```php
$result = strtoupper('akashi');

// @akashi-phpstan-error akashi.phpunit10.assert: native assert call discovered
assert($result === 'AKASHI');
```

<!-- akashi: skip -->

```php
throw new RuntimeException('A skipped example must not execute.');
```

```php
// akashi: expect-exception=Exception
// akashi: expect-exception-message="expected subtype", expect-exception-code=73, expect-output="before expected subtype\n"

echo "before expected subtype\n";
throw new RuntimeException('An expected subtype satisfies its parent type.', 73);
```

<!-- akashi: separate-process -->

```php
namespace Akashi\PHPUnit10Compatibility\SeparateProcess;

// akashi: expect-exception=Akashi\PHPUnit10Compatibility\SeparateProcess\CompatibilityException
// akashi: expect-exception-message="expected child subtype", expect-exception-code=81, expect-output="before child subtype\n"

final class CompatibilityException extends \RuntimeException {}

echo "before child subtype\n";
throw new CompatibilityException('An expected child subtype preserves its evidence.', 81);
```
