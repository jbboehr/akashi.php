# Documentation logion plates

This document records the curated relationship between each public mdBook page, one existing Akashi logion, and one
original illustration. The source declaration remains the canonical assignment and text of each logion. Its appearance
in the book is a cited republication, not a second allocation or a semantic annotation of the declaration.

Selection favors literary quality and thematic resonance with the page while preserving every source assignment. Images
interpret the selected logion under the Doctrine image guide; they do not depict software concepts literally. Gold
exemplars establish the quality ceiling and layout precedent but are not reused as text, plots, or artwork.

Each final illustration has a 3840-by-2160 archival WebP at `docs/development/images/logia/BOOK-CHAPTER_VERSE-hq.webp`
and a 960-by-540 delivery WebP at `docs/pages/images/logia/BOOK-CHAPTER_VERSE.webp`. The latter has one-sixteenth the
pixel area and is the only version embedded in the book. Both versions remain repository assets, while Composer excludes
them with the rest of their respective documentation trees. The explicit `-hq` rule is a defensive Git `export-ignore`
rule beneath the already excluded development tree; Composer relies on its tree-level exclusions. This redundancy
preserves the archival boundary if packaging changes later.

Each public page except the Introduction receives exactly one plate; the Introduction's existing banner already provides
its ceremonial artwork. The quotation appears to the left and the illustration to the right on wide screens, then stacks
on narrow screens. Each plate appears directly below its page title, before the technical introduction begins.

| Page                              | Citation  | Canonical source             | Visual center                                                               | Status                |
| --------------------------------- | --------- | ---------------------------- | --------------------------------------------------------------------------- | --------------------- |
| `README.md`                       | —         | —                            | Existing Akashi banner; no additional plate                                 | Intentional exception |
| `quick-start.md`                  | OSD 18:2  | `Example`                    | A hollow bone answering thunder above an unformed marsh                     | Complete              |
| `using/index.md`                  | OSD 13:44 | `ExampleCorpus`              | Black swans teaching a still ocean to bear traveling waves                  | Complete              |
| `using/authoring.md`              | OSD 30:27 | `MarkdownSource`             | One seed and a caravan of jars received at the same harvest gate            | Complete              |
| `using/phpunit.md`                | OSD 59:1  | `NativeAssertion`            | A common stone weighed once beneath a judicial lamp                         | Complete              |
| `using/phpstan.md`                | RAS 66:9  | `VerifiesPhpStanExamples`    | Seven stars compared with their reflections above a glass city              | Complete              |
| `using/separate-process.md`       | AWC 62:10 | `SubprocessExecutor`         | A witness crossing alone into a distant temporary tribunal                  | Complete              |
| `using/extracting.md`             | SFA 48:40 | `MarkedExampleSelector`      | A child clearing a mill channel while the waiting white horse departs       | Complete              |
| `guides/index.md`                 | AWC 42:3  | `CommonMarkExampleExtractor` | A humble linen cloth safely receiving a newborn beside ceremonial silk      | Complete              |
| `guides/test-documentation.md`    | RAS 47:26 | `CommonMarkExampleExtractor` | Rising snow revealing the patient repairs of an observatory dome            | Complete              |
| `guides/reuse-runtime-phpstan.md` | RAS 31:24 | `MarkdownSource`             | Two processions crossing a glass mountain with one shared wound             | Complete              |
| `guides/diagnosing-failures.md`   | AWC 57:15 | `FailurePhase`               | A physician distinguishing wounds of blade and bandage by their hour        | Complete              |
| `reference/index.md`              | AWC 2:31  | `Example`                    | A scholar planting unanswered walnuts until the garden outlives the library | Complete              |
| `reference/configuration.md`      | RAS 61:9  | `RuntimeConfiguration`       | An immutable chart bearing court, preparatory scroll, and ordinary road     | Complete              |
| `reference/directives.md`         | AWC 4:37  | `Directive`                  | A dancer rehearsing the fall that later guides another safely downward      | Complete              |
| `reference/cli.md`                | SFA 52:45 | `UsageException`             | A silver theatrical mask completing one sorrow before the multitude         | Complete              |
| `reference/api.md`                | OSD 6:14  | `DocumentPath`               | Wandering celestial lights making room for one another to burn              | Complete              |
| `reference/compatibility.md`      | AWC 55:2  | `InProcessSafetyValidator`   | Wardens inspecting every hinge and passage before a procession              | Complete              |
| `project/index.md`                | AWC 17:42 | `Application`                | A child scattering the moon's reflection from a deep well                   | Complete              |
| `project/architecture.md`         | OSD 53:1  | `ExecutionMode`              | Cedar and bronze vessels sharing one spring while serving unlike offices    | Complete              |
| `project/invariants.md`           | SFA 53:20 | `SourceMap`                  | A measured margin ending at the last trustworthy lamp                       | Complete              |
| `project/roadmap.md`              | RAS 33:21 | `MarkdownSource`             | Abandoned milestones naming a kingdom seven generations before its founding | Complete              |

## Acceptance rules

- Preserve the canonical source text and citation exactly.
- Use each citation and illustration on only one public page.
- Keep image text absent; the adjacent HTML supplies the quotation and citation.
- Give each image concise alt text describing its visible content rather than repeating the quotation.
- Place each plate directly below the page-level title.
- Include a recognizable and meaningful retrowave or synthwave anchor in every image.
- Maintain one visual civilization across the series without mechanically repeating compositions or motifs.
- Verify responsive rendering, meaningful alt text, source-text fidelity, image existence, and page coverage.
