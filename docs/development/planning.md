# Planning

The repository contains the validated runtime dependencies, immutable document and example models, deterministic
Markdown discovery, CommonMark PHP-fence extraction, configurable marker association and selection, execution directive
parsing, and the framework-independent marked-example extraction CLI. The current implementation slice adds the PHP
transformation foundation; execution and verification APIs follow it.

`nikic/php-parser` and `symfony/process` are intentionally installed before their first imports. Their constraints were
validated with the other runtime dependencies in the first implementation slice. `nikic/php-parser` now supports the
transform foundation, while `symfony/process` remains reserved for the later separate-process slice.

The transform compatibility gate currently prepares 35 of Yumemi's 37 reference examples. Two authored-namespace
examples are intentionally rejected under the recorded MVP policy and must receive explicit separate-process selection
or an owner-approved policy revision before the Yumemi migration can claim complete runtime coverage.

The detailed implementation handoff is maintained separately from the public mdBook sources.
