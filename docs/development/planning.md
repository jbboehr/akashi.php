# Planning

The repository contains the validated runtime dependencies, immutable document and example models, deterministic
Markdown discovery, CommonMark PHP-fence extraction, configurable marker association and selection, and execution
directive parsing. The extraction CLI is the next implementation slice; transformation, execution, and verification APIs
follow it.

`nikic/php-parser` and `symfony/process` are intentionally installed before their first imports. Their constraints were
validated with the other runtime dependencies in the first implementation slice, and they are reserved for the planned
transform and separate-process slices respectively.

The detailed implementation handoff is maintained separately from the public mdBook sources.
