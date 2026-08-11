# Using Akashi

<figure class="logion" data-logion="OSD 13:44">
<div class="logion-text">
<blockquote>
<p>Before the oceans knew motion, they lay heavy and still beneath a copper sky. A flock of black swans beat their wings
across the surface, raising the first waves and teaching depth to travel without departure. Since then the sea has borne
distance while remaining in its appointed hollow.</p>
</blockquote>
<p class="logion-citation">— <cite>Ordinances of the Synthetic Dawn 13:44</cite></p>
</div>
<img src="../images/logia/OSD-13_44.webp" alt="Black swans raising luminous first waves across a still ocean beneath a copper sky" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Akashi separates discovering documentation examples from deciding what to do with them. Build one `ExampleCorpus`, then
hand it to the integration needed by the project:

- [PHPUnit](phpunit.md) executes examples as named data sets.
- [PHPStan](phpstan.md) analyzes a selected subcorpus and checks expected diagnostics.
- [Extracting Named Examples](extracting.md) emits one author-marked fence as a consumer fixture.

Most projects begin with the in-process PHPUnit path from the [Quick Start](../quick-start.md). Add
[separate-process execution](separate-process.md) only to examples that require it, and add PHPStan only when the
project has a rule or analysis behavior worth demonstrating.

[Authoring Examples](authoring.md) describes the shared Markdown/PHPDoc corpus used by all three workflows.
