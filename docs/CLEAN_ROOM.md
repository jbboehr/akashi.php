# Clean-room record

Akashi's implementation is being developed without consulting the source code of existing documentation-test frameworks.

## Prohibited implementations

No implementation code was consulted from:

- `testflowlabs/doctest`;
- `texthtml/doctest`;
- `monadial/phpunit-docrunner`;
- `hoaproject/Kitab`;
- Cargo or rustdoc's doctest implementation; or
- any other documentation-test framework.

No accidental exposure to prohibited implementation details occurred while creating this scaffold.

## Materials consulted

The scaffold was adapted only from these user-owned local repositories:

- `tmp/imm.php` (`jbboehr/yumemi`);
- `tmp/yumemi-apocrypha.php` (`jbboehr/yumemi-apocrypha`); and
- the project requirements in `docs/IMPLEMENTATION_HANDOFF.md`.

No external implementation source was browsed.

## Dependencies

Akashi currently has no runtime dependencies beyond PHP 8.2 or later. Its development dependencies are:

- PHPUnit, for project tests and coverage;
- PHPStan plus its PHPUnit and strict-rules extensions, for static analysis;
- PHP-CS-Fixer, for coding style; and
- Infection, for mutation testing.

These are general testing, analysis, and formatting tools rather than documentation-test frameworks. Their installed
implementation files were not used as design references.
