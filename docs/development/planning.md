# Planning

The Markdown MVP is implemented as a reusable library: deterministic CommonMark discovery, markers and directives,
in-process and child-process execution, PHPUnit data sets and reporting, PHPStan `RuleTestCase` verification, and the
marked-example extraction CLI all have typed contracts and repository coverage.

Yumemi's runtime and PHPStan migration is complete. All 43 current PHP fences execute through Akashi, including two
authored-namespace examples routed to child processes, and all 15 relevant PHPStan examples are verified. Akashi's local
compatibility fixtures are self-contained and preserve all eight Apocrypha extraction outputs byte-for-byte.

Yumemi Apocrypha completed its migration in commit `f617093eeca3cf6be21907f596f15673c545927c`. Its eight marked consumer
fixtures now use the Akashi CLI, and its duplicated extractor and tests were removed after compatibility was
established. GitHub recorded 164 completed, successful check runs for that commit across the normal and isolated
consumer matrices.

All recorded MVP consumer acceptance gates are therefore complete. The next project task is a pre-release review of the
public API and documented limitations. No committed Akashi code or tests may depend on workspace checkout paths during
future consumer verification.

ParaTest compatibility is verified with two workers in both default TestCase-level and `--functional` test-level modes.
`composer test:parallel` runs both variants; CI exercises the gate on PHP 8.2 while sequential tests cover the remaining
PHP matrix.

The current public architecture is documented in `docs/pages/project/architecture.md`. Detailed historical requirements
and clean-room constraints remain in `docs/IMPLEMENTATION_HANDOFF.md` and `docs/CLEAN_ROOM.md`.
