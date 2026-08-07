# Roadmap

This roadmap records design direction rather than release-number commitments. The immediate priority is to complete the
Yumemi and Yumemi Apocrypha acceptance gates, incorporate any reusable-library feedback they expose, and then stabilize
the documented MVP API.

## Complete the Markdown MVP

1. Migrate Yumemi's runtime documentation test without silently omitting unsupported examples.
2. Verify Yumemi's complete relevant PHPStan corpus and all authored expectations.
3. Migrate Yumemi and Apocrypha marked-example extraction calls.
4. Remove duplicated consumer helpers only after both old and Akashi paths agree.
5. Finalize public API, limitation, and migration documentation from the acceptance evidence.

The ParaTest compatibility gate is complete for both TestCase-level and `--functional` test-level scheduling. Keep both
modes in CI while the consumer migrations exercise the same public integration paths.

## PHPDoc Example Maintainability

Future PHPDoc support should offer three authoring modes:

1. Short inline PHPDoc fences for local demonstrations.
2. References to ordinary external PHP files or stable named regions, with the external file as the source of truth.
3. Optional synchronized inline copies for documentation renderers that cannot include external content.

Referenced canonical examples are preferred for substantial code because IDEs, formatters, PHPStan, and the PHP runtime
can operate on them directly. Named regions are preferred over fragile line-number ranges.

The planned sequence is:

1. PHPDoc fenced examples.
2. External canonical PHP examples and named regions.
3. Generalized source-location mapping.
4. Check-only synchronization.
5. Check-only formatter integration.
6. Optional write-mode synchronization and formatting.
7. Hidden support-code semantics.
8. Documentation-renderer integrations.

No hidden-line syntax is selected. Any future design should remain explicit and compatible with PHP parsers, formatters,
IDEs, renderers, and static analyzers.

## Runtime and Verification

Deferred runtime work includes a typed PHPUnit-familiar expected-exception model, expected output, explicit skip and
ignore outcomes, compile-only checks, platform conditions, configurable subprocess timeouts, alternate PHP binaries and
INI profiles, and controlled child environments.

The PHPStan roadmap includes an identifier-oriented expectation syntax that coexists with `//!`, richer verifier result
objects outside PHPUnit, and source maps capable of composing multiple transformations without reporting only temporary
file locations.

A standalone runner, reporter formats, and broader plugin seams should follow concrete consumer demand. Akashi will not
add registries or placeholder interfaces merely to anticipate them.

## Comparative Review

After the MVP architecture and public API are implemented and recorded, the owner may request a separate
documentation-only comparison with competing PHP doctest projects. That review is not part of current implementation,
must record every external document consulted, and must not inspect implementation code or silently reshape Akashi's
foundational APIs to match another project.
