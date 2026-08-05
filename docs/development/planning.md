# Planning

The repository contains the validated runtime dependencies, immutable document and example models, deterministic
Markdown discovery, CommonMark PHP-fence extraction, configurable marker association and selection, and execution
directive parsing, plus the framework-independent marked-example extraction CLI. The PHP transformation foundation is
the next implementation slice; execution and verification APIs follow it.

`nikic/php-parser` and `symfony/process` are intentionally installed before their first imports. Their constraints were
validated with the other runtime dependencies in the first implementation slice. `nikic/php-parser` is reserved for the
next transform slice and `symfony/process` for the later separate-process slice.

The detailed implementation handoff is maintained separately from the public mdBook sources.
