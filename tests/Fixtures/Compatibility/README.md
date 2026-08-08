# Consumer compatibility fixture provenance

These fixtures are self-contained snapshots derived from the user-owned compatibility targets that Akashi is explicitly
permitted to inspect:

- `Yumemi/README.md` is the reduced supported example already retained by Akashi from `jbboehr/yumemi`.
- `Yumemi/docs/pages/recipes.md` preserves example 4 from the Yumemi reference snapshot at
  `eea3e49f1d5a991271f692a8ba22d3149ceb905c`.
- `Yumemi/docs/pages/reference/phpstan.md` preserves example 6 from the same snapshot.
- `Apocrypha/marked-examples.md` consolidates the eight marked examples from `jbboehr/yumemi-apocrypha` at
  `80cc826de87db44ab10c87abe3a32baf5c27614a`.
- `Apocrypha/expected/*.txt` contains the byte-for-byte PHP output produced for those markers by the legacy Apocrypha
  extractor at that snapshot, including the final LF.

Both source projects are owned by Akashi's owner and use the same `AGPL-3.0-only WITH romic-exception` licensing model.
The fixtures are committed so compatibility tests never depend on workspace-local reference checkouts.
