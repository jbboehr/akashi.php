# Contributing

Thank you for considering a contribution to this project.

Bug reports, feature suggestions, documentation improvements, tests, and code changes are welcome. For substantial
changes, consider opening an issue first so that the proposed design can be discussed.

## Pull requests

A pull request should:

- explain what it changes and why;
- include or update relevant tests;
- pass the project’s formatting, static-analysis, and test checks;
- avoid unrelated changes; and
- identify any third-party code, assets, or other material it contains.

Enter the Nix development shell and install the locked Composer dependencies for ordinary interactive work:

```shell
nix develop
composer install
```

This keeps the checkout's `vendor/` mutable and conventional. Run focused tools through Composer while developing, then
run the authoritative routine repository gate with:

```shell
nix flake check --keep-going -L
```

The flake exposes PHPUnit separately for PHP 8.1 through 8.5 and also checks PHPStan, PHP-CS-Fixer, Composer metadata,
the root and auxiliary Composer lock pairs, PHP syntax, package contents, both ParaTest scheduling modes, the public
documentation, benchmark discovery, repository formatting and hooks, and the PHPUnit 10 and PHPStan 1 consumer fixtures.
These builds use Nix-managed Composer dependencies and do not read the checkout's `vendor/`.

Mutation testing is deliberately not part of `nix flake check`. Run its explicit Nix target when a change warrants it:

```shell
nix build .#mutation -L
```

`composer check` and `composer check:full` remain useful mutable-Composer interfaces. The latter includes consumer,
ParaTest, and mutation checks, but the separated Nix derivations are the reproducible validation authority.

### Updating Nix Composer dependencies

Nix builds four immutable Composer repositories: the normal locked development closure, the PHP 8.1 closure, the lowest
supported dependency closure, and the PHPStan 1 consumer closure. Their locks live in `composer.lock` and
`nix/composer/`; their corresponding `vendorHash` values live together in `nix/php-checks.nix`.

The PHP 8.1 closure supplies the PHPUnit 10 consumer lock, while the PHPStan 1 closure supplies the PHPStan 1 consumer
lock. Regenerate each closure lock and its paired consumer lock together so every pinned consumer package remains in its
offline repository. The consumer checks also reject a lock whose `jbboehr/akashi` entry no longer describes the current
package metadata.

When Composer requirements or locks change:

1. Update `composer.json` and `composer.lock` normally and refresh any affected lock under `nix/composer/`.
2. Run `nix flake check --keep-going -L`.
3. If a `vendorHash` is stale, copy the `got: sha256-...` value from Nix's fixed-output hash mismatch into the matching
   closure in `nix/php-checks.nix`.
4. Rerun `nix flake check --keep-going -L`.

The lowest-dependency lock is a reviewed reproducible snapshot rather than a fresh resolution on every CI run. Changes
to `composer.json` invalidate its manifest/lock validation and therefore require the snapshot to be regenerated with
`--prefer-lowest --prefer-stable`.

If Nix is unavailable locally, push the lock change to a branch or pull request. The failed generated Nix job preserves
the original mismatch, repeats the current and replacement hashes near the end of its log, and writes the replacement to
the GitHub Actions step summary. Updating the hash does not replace review of the lock diff.

Release maintainers must follow [`docs/development/releasing.md`](docs/development/releasing.md).

AI-assisted contributions are permitted, but you remain responsible for reviewing the submitted material and ensuring
that you have the right to license it under these terms.

See [`docs/development/mutation-testing.md`](docs/development/mutation-testing.md) for the explicit mutation-testing
workflow and guidance on interpreting escaped mutants.

See [`docs/development/benchmarking.md`](docs/development/benchmarking.md) for timing benchmarks, local comparisons, and
optional Linux performance-counter measurements.

## Doctrine of the Second Sun

Akashi adopts the literary, coding, image, generation, and exemplar guidance, the Measure of Words for technical
writing, and Ruinenwert preservation guidance from the Composer-pinned `jbboehr/doctrine-of-the-second-sun` development
dependency. Run `composer install`, then read the portable guides under `vendor/jbboehr/doctrine-of-the-second-sun/`
together with Akashi's repository-specific rules in [`AGENTS.md`](AGENTS.md). The local rules govern where doctrine
applies, citation allocation, preservation, and verification.

## Documentation

The public mdBook sources live under [`docs/pages`](docs/pages). Internal engineering documents live under
[`docs/development`](docs/development). Legal and contributor documents remain directly under [`docs`](docs). Internal
documents are not included in the generated site.

Enter the Nix development shell, then build or preview the public documentation with:

```shell
make docs
make docs-serve
```

The generated site is written to `build/docs`. Documentation examples should be exercised by the project's doctesting
suite as that functionality is introduced.

## Definitions

The project as a whole is distributed under:

```text
AGPL-3.0-only WITH romic-exception
```

This is the **Project License**.

The default license for contributor-authored material is:

```text
AGPL-3.0-only WITH romic-exception OR Apache-2.0
```

This is the **Default Contribution License**.

A **Contribution** is copyrightable material intentionally submitted for inclusion in the project, including code,
documentation, tests, configuration, and artwork.

Issue reports, feature requests, general discussion, and material conspicuously marked **“Not a Contribution”** are not
Contributions under these terms.

The **Project Steward** is the individual or legal entity identified in [`docs/STEWARD.md`](docs/STEWARD.md).

## Default contribution terms

By intentionally submitting a Contribution to this repository through a pull request or another contribution mechanism
that provides notice of these terms, you license the Contribution to every recipient under the Default Contribution
License unless you validly elect the CLA route described below.

Under the default route:

- you retain copyright in your Contribution;
- each recipient may use your Contribution under either listed license;
- the public project may incorporate your Contribution under the Project License;
- the Project Steward may use your Contribution under Apache-2.0, including in separately licensed or proprietary
  versions;
- every other recipient receives the same Apache-2.0 option;
- the Apache-2.0 option applies only to material that you have the right to license; and
- your Contribution does not cause the remainder of the project to become licensed under Apache-2.0.

The default route is intentionally symmetric. The Project Steward receives no Apache-2.0 permission that is withheld
from the public.

No checkbox is required to use the default route.

## Optional CLA route

A contributor who prefers their Contribution to remain publicly available only under the Project License may instead
affirmatively elect the project’s Contributor License Agreement in the applicable pull request.

Under the CLA route:

- the Contribution is publicly licensed under the Project License;
- the Project Steward receives the additional rights specified in the CLA; and
- the Contribution is not intentionally offered to the public under Apache-2.0 by these contribution terms.

The pull-request checkbox must identify and link to the applicable version of the CLA.

By checking that box, the person making the election:

1. confirms that they have read and agree to the identified CLA;
2. elects the CLA route for the Contribution;
3. represents that they own all rights necessary to make that election or are authorized to act for every other
   applicable copyright holder; and
4. accepts the CLA for themselves and, to the extent of their authority, on behalf of those other copyright holders.

The CLA becomes effective for a Contribution when the Project Steward merges or otherwise incorporates that Contribution
into the project. No separate countersignature or signing process is required unless the applicable CLA expressly
provides otherwise.

## Multiple authors and partial elections

The person electing the CLA route may make that election for material owned by another person or organization only when
authorized to act on that copyright holder’s behalf.

A CLA election applies only to portions of a Contribution for which the person making the election owns the necessary
rights or possesses the necessary authority.

To the extent that a CLA election is invalid, unauthorized, incomplete, or otherwise ineffective for any portion of a
Contribution, that portion remains subject to the Default Contribution License, provided that it was otherwise validly
submitted under these contribution terms.

Accordingly, a single Contribution may contain:

- material validly governed by the CLA route; and
- material governed by the Default Contribution License.

An ineffective CLA election does not invalidate the Default Contribution License for material otherwise validly
submitted under these terms.

## Unauthorized material

Neither the Default Contribution License nor a CLA election grants rights in material that the submitter had no
authority to license.

A false representation of ownership or authority does not bind the actual copyright holder or cure an unauthorized
submission.

If a Contribution contains material that was not validly submitted or licensed, the Project Steward may remove, replace,
or seek separate permission for that material.

## Your authority to contribute

By submitting a Contribution, you represent that:

1. you created the Contribution or otherwise have sufficient rights to submit it under the applicable terms;
2. if another person or organization owns any portion of the Contribution, you are authorized to submit and license that
   portion;
3. you have obtained any necessary permission from your employer or another rights holder;
4. you have identified any third-party material included in the Contribution and its applicable license; and
5. you are not knowingly submitting material that cannot lawfully be incorporated into the project.

Do not submit code copied from another project merely because that project is publicly accessible. Identify its source
and license so that compatibility can be reviewed.

## Copyright

You retain copyright in your Contribution. Submission does not assign copyright to the Project Steward.

Accepted contributions may be acknowledged collectively using a notice such as:

```text
Copyright (c) [YEAR] [PROJECT_STEWARD] & contributors
```

Individual copyright notices may be retained where legally required or reasonably appropriate.

## Acceptance

The Project Steward may accept, reject, request changes to, or decline to incorporate any Contribution.

For unusually large contributions, corporate contributions, or contributions with unclear provenance, the Project
Steward may request additional confirmation of ownership or authority before merging.
