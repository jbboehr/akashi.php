# ParaTest compatibility examples

```php
<?php

function akashi_parallel_fixture_value(): int
{
    return 1;
}

assert(akashi_parallel_fixture_value() === 1);
```

```php
<?php

function akashi_parallel_fixture_value(): int
{
    return 2;
}

assert(akashi_parallel_fixture_value() === 2);
```

<!-- akashi: separate-process -->

```php
<?php

namespace Akashi\ParaTestFixture;

assert(is_file('composer.json'));
```

<!-- akashi: separate-process -->

```php
<?php

namespace Akashi\ParaTestFixture;

final class RepeatedDeclaration
{
}

assert(class_exists(RepeatedDeclaration::class, false));
```
