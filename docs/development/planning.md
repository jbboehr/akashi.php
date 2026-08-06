# Planning

The repository contains the validated runtime dependencies, immutable document and example models, deterministic
Markdown discovery, CommonMark PHP-fence extraction, configurable marker association and selection, execution directive
parsing, the framework-independent marked-example extraction CLI, the PHP transformation foundation, typed
execution-result contracts, in-process state-restoration guards, and source-preserving native assertion rewriting
through the PHPUnit assertion bridge. In-process code execution, named PHPUnit data sets, result reporting, and the
stateless PHPUnit runtime facade are implemented. Separate-process execution and PHPStan verification follow in
subsequent working chunks.

`nikic/php-parser` and `symfony/process` are intentionally installed before their first imports. Their constraints were
validated with the other runtime dependencies in the first implementation slice. `nikic/php-parser` now supports the
transform foundation, while `symfony/process` remains reserved for the later separate-process slice.

The transform compatibility gate currently prepares 35 of Yumemi's 37 reference examples. Two authored-namespace
examples are intentionally rejected under the recorded MVP policy and must receive explicit separate-process selection
or an owner-approved policy revision before the Yumemi migration can claim complete runtime coverage.

The detailed implementation handoff is maintained separately from the public mdBook sources.
