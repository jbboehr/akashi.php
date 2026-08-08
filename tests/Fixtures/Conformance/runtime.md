# Runtime conformance fixtures

```php
<?php

function conformance_declaration(): string
{
    return 'first';
}

assert(conformance_declaration() === 'first');
```

```php
<?php

function conformance_declaration(): string
{
    return 'second';
}

assert(conformance_declaration() === 'second');
```

<!-- akashi: separate-process -->

```php
<?php

namespace Akashi\ConformanceFixture;

assert(__NAMESPACE__ === 'Akashi\\ConformanceFixture');
```
