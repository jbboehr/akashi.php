# Using Akashi

Akashi separates discovering documentation examples from deciding what to do with them. Build one `ExampleCorpus`, then
hand it to the integration needed by the project:

- [PHPUnit](phpunit.md) executes examples as named data sets.
- [PHPStan](phpstan.md) analyzes a selected subcorpus and checks expected diagnostics.
- [Extracting Named Examples](extracting.md) emits one author-marked fence as a consumer fixture.

Most projects begin with the in-process PHPUnit path from the [Quick Start](../quick-start.md). Add
[separate-process execution](separate-process.md) only to examples that require it, and add PHPStan only when the
project has a rule or analysis behavior worth demonstrating.

[Authoring Examples](authoring.md) describes the shared Markdown corpus used by all three workflows.
