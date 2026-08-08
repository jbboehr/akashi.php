# Roadmap

This roadmap records design direction rather than release-number commitments. Yumemi's runtime and PHPStan acceptance
gates are complete. The immediate priority is to complete the Yumemi Apocrypha consumer migration, incorporate any
remaining reusable-library feedback, and then stabilize the documented MVP API.

## Complete the Markdown MVP

Completed acceptance evidence:

- Yumemi executes all 43 current PHP fences through Akashi without silent omission, using the separate-process backend
  for its two authored-namespace examples.
- Yumemi verifies all 15 relevant PHPStan examples and eight authored expectations through Akashi.
- Yumemi removed its duplicated documentation-test helpers after the replacement suite and complete project gate passed.
- Akashi produces byte-identical output for all eight marked Apocrypha fixtures in its local compatibility gate.

Remaining MVP work:

1. Migrate Apocrypha's eight marked-example consumer calls to `vendor/bin/akashi`.
2. Run Apocrypha's normal and consumer suites, then remove its duplicated extractor only after both paths agree.
3. Finalize the public API, limitations, and migration documentation from both consumers' acceptance evidence.

The ParaTest compatibility gate is complete for both TestCase-level and `--functional` test-level scheduling. Keep both
modes in CI while the remaining consumer migration exercises the same public integration paths.

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

Authored runtime skip is implemented through PHPUnit's ordinary skipped-test reporting while preserving the example in
its corpus. Deferred runtime work includes global ignore and conditional skip policies with explicit reasons, a typed
PHPUnit-familiar expected-exception model, expected output, compile-only checks, platform conditions, configurable
subprocess timeouts, alternate PHP binaries and INI profiles, and controlled child environments.

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
