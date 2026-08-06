# Planning

The repository contains the validated runtime dependencies, immutable document and example models, deterministic
Markdown discovery, CommonMark PHP-fence extraction, configurable marker association and selection, execution directive
parsing, the framework-independent marked-example extraction CLI, the PHP transformation foundation, typed
execution-result contracts, in-process state-restoration guards, and source-preserving native assertion rewriting
through the PHPUnit assertion bridge. In-process code execution, named PHPUnit data sets, result reporting, and the
stateless PHPUnit runtime facade are implemented. Separate-process execution and PHPStan verification follow in
subsequent working chunks. The separate-process preparation seam now includes canonical project/bootstrap configuration,
backend-specific prepared examples, and source-preserving normal-file preparation; process invocation, temporary
artifacts, and result capture remain in the next working chunk.

Before the first release, verify the documented PHPUnit integration under ParaTest. Exercise independently named data
sets concurrently and confirm deterministic discovery, collision-resistant execution scopes, process-state isolation,
failure reporting, and temporary-resource cleanup. Treat this as compatibility verification rather than a separate
runner integration, and do not add a runtime dependency on ParaTest solely for this check.

`nikic/php-parser` and `symfony/process` are intentionally installed before their first imports. Their constraints were
validated with the other runtime dependencies in the first implementation slice. `nikic/php-parser` now supports the
transform foundation, while `symfony/process` remains reserved for the later separate-process slice.

The transform compatibility gate currently prepares 35 of Yumemi's 37 reference examples. Two authored-namespace
examples are intentionally rejected under the recorded MVP policy and must receive explicit separate-process selection
or an owner-approved policy revision before the Yumemi migration can claim complete runtime coverage.

The detailed implementation handoff is maintained separately from the public mdBook sources.
