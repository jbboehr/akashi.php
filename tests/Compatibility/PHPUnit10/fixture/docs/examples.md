# PHPUnit 10 compatibility fixture

```php
$result = strtoupper('akashi');

//! native assert call discovered
assert($result === 'AKASHI');
```

<!-- akashi: skip -->

```php
throw new RuntimeException('A skipped example must not execute.');
```

<!-- akashi: separate-process -->

```php
namespace Akashi\PHPUnit10Compatibility\SeparateProcess;

assert(PHP_VERSION_ID >= 80200);
```
