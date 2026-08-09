# Releasing Akashi

Akashi releases are prepared and published manually. CI verifies the same repository-owned commands, but it has no
GitHub or Packagist publishing authority.

## Release prerequisites

A release steward needs:

- a clean checkout of the intended `master` commit with all tags fetched;
- the Nix development shell and locked Composer dependencies;
- authority to push a tag to the publishing repository;
- an available signing key whose identity can be verified by maintainers;
- GitHub release access; and
- Packagist access for the package identity being published.

Do not begin a release while repository or package ownership is disputed, the intended commit is not green, or artifact
provenance cannot be established. Follow the fork procedure in [`docs/SUCCESSION.md`](../SUCCESSION.md) when the
original publishing identity is unavailable.

## Prepare the release commit

1. Confirm `git status --short` is empty and `git branch --show-current` is `master`.
2. Run `git fetch --tags` and confirm the release commit is the intended public branch tip.
3. Enter `nix develop` and run `composer install --no-interaction` so the committed lock is used without updating it.
4. For the first tag, replace the empty changelog template with an accurate initial release entry. For later tags, move
   the accumulated `[Unreleased]` entries beneath the release version and date while retaining a new empty
   `[Unreleased]` section.
5. For the first tag, replace every `dev-master` installation example and every statement that Akashi has no tagged
   release. Review `README.md`, `docs/pages/README.md`, the Quick Start, compatibility reference, API reference, and
   roadmap together so the tagged source does not contradict itself. Keep accurate pre-1.0 stability qualifications;
   publishing a first tag does not make the API stable by itself.
6. Commit the changelog or any other release-only metadata, rerun the gates below, and require a clean worktree again.

Do not add promises for deferred roadmap work. The release notes describe only behavior present in the tagged commit.

## Verify source and artifact

Run the routine and extended gates:

```console
composer check
composer check:full
composer audit --locked
```

The extended gate runs the isolated PHPUnit 10 consumer fixture, both ParaTest modes, and mutation testing, and
therefore requires Composer network access and a coverage driver. A failure or tool crash blocks the release until it is
understood; do not lower mutation thresholds or omit a supported compatibility or scheduling mode merely to produce a
tag.

Build the exact Composer distribution artifact in the ignored release directory:

```console
mkdir -p build/release
composer archive --format=tar --dir=build/release --file=akashi-X.Y.Z
composer package:check -- build/release/akashi-X.Y.Z.tar
sha256sum build/release/akashi-X.Y.Z.tar
```

Replace `X.Y.Z` with the release version without the leading `v`. Record the checksum in the GitHub release notes. The
package check validates that exact archive rather than creating and checking a separate artifact. Before tagging,
inspect the archive manifest and confirm it contains runtime source, `bin/akashi`, Composer metadata, the licenses,
README, changelog, and public documentation, while excluding tests, tools, Nix files, caches, and workspace paths.

## Tag and publish

1. Create a signed annotated SemVer tag at the verified commit:

   ```console
   git tag -s vX.Y.Z -m "Akashi X.Y.Z"
   ```

2. Verify the tag locally with `git tag -v vX.Y.Z` and confirm `git status --short` remains empty.
3. Push the tag without moving any existing tag: `git push origin vX.Y.Z`.
4. Create a GitHub release from that tag. Attach `akashi-X.Y.Z.tar`, include its SHA-256 checksum, and use the prepared
   changelog entry as the release notes.
5. For the first canonical release, register `https://github.com/jbboehr/akashi.php` as `jbboehr/akashi` on Packagist if
   it has not already been registered. For later releases, verify Packagist has imported the exact tag and constraints.
6. Verify the documentation workflow completed for `master` and the public Pages site still resolves.

Signing-key rotation is allowed. Record the new public key and transition in the release notes or repository history;
successors do not need, and must never receive, a former steward's private key.

## Fresh-consumer verification

In a new directory outside the source checkout, create an empty Composer project and require the exact published version
plus PHPUnit:

```console
composer require --dev jbboehr/akashi:X.Y.Z phpunit/phpunit:^11.5
vendor/bin/akashi --version
vendor/bin/akashi --help
```

Then copy the Quick Start test and Markdown example into that project and run `vendor/bin/phpunit`. This verifies the
Packagist metadata, installed executable proxy, production autoloading, optional PHPUnit dependency, and documented
consumer path rather than only the source checkout.

## Correcting a bad release

Published tags and artifacts are immutable evidence. Never move, replace, or silently rebuild a published tag. If a
release is defective:

1. record the problem publicly;
2. fix it on `master` with ordinary review and gates;
3. publish a new patch version; and
4. mark the old GitHub release or Packagist version as affected without erasing it.

If the tag was pushed but never made public through GitHub or Packagist, stop and document the exact state before taking
further action. Do not reuse the version while another observer could have fetched it.
