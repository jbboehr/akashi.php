# Planning

The repository contains the validated runtime dependencies, immutable document and example models, deterministic
Markdown discovery, CommonMark PHP-fence extraction, configurable marker association and selection, execution directive
parsing, the framework-independent marked-example extraction CLI, the PHP transformation foundation, typed
execution-result contracts, in-process state-restoration guards, and source-preserving native assertion rewriting
through the PHPUnit assertion bridge. In-process code execution, named PHPUnit data sets, result reporting, and the
stateless PHPUnit runtime facade are implemented. The separate-process backend now includes canonical project/bootstrap
configuration, backend-specific prepared examples, source-preserving normal-file preparation, secure temporary
artifacts, process invocation, typed failures, source-mapped diagnostics, and result capture. Directive/configuration
routing through the PHPUnit facade is implemented, including explicit project-root requirements, configured defaults,
bootstrap handling, and common result reporting. The analyzer-independent PHPStan expectation foundation is also
implemented: ordered `//!` parsing retains maintained Markdown lines, diagnostics remain free of PHPStan runtime types,
and exact-count, case-sensitive message/tip matching uses deterministic one-to-one assignment with typed outcomes.
Canonical PHPStan project configuration, explicit custom or supplied-token relevance predicates, and deterministic
nonempty relevant-corpus selection are implemented without embedding Yumemi-specific tokens. Its secure temporary-source
lifecycle and public `RuleTestCase` adapter now load declarations, supply diagnostics, map maintained source lines, and
report matcher outcomes. The remaining MVP work is driven by the Yumemi and Yumemi Apocrypha consumer migrations.

Compatibility with the locked ParaTest release is verified with two workers in both its default TestCase-level mode and
its `--functional` test-level mode. The full suite includes consumer-shaped named documentation data sets, in-process
declaration isolation, separate-process execution, temporary-resource cleanup, failure reporting, and the PHPStan
`RuleTestCase` adapter. `composer test:parallel` repeats both modes, and CI runs the gate on PHP 8.2. ParaTest remains
development-only; Akashi has no runner-specific runtime integration.

`nikic/php-parser` and `symfony/process` are intentionally installed before their first imports. Their constraints were
validated with the other runtime dependencies in the first implementation slice. `nikic/php-parser` now supports the
transform foundation, while `symfony/process` supports the standalone separate-process executor.

The transform compatibility gate currently prepares 35 of Yumemi's 37 reference examples. Two authored-namespace
examples are intentionally rejected under the recorded MVP policy and must receive explicit separate-process selection
or an owner-approved policy revision before the Yumemi migration can claim complete runtime coverage.

The detailed implementation handoff is maintained separately from the public mdBook sources.
