# Fork-Oriented Succession

Akashi must remain maintainable when its current steward, repository, or package-publishing accounts are unavailable.
Continuity therefore depends on the complete public repository and reproducible contracts, not on transfer of the
original accounts.

The current steward is recorded in [`STEWARD.md`](STEWARD.md). That designation identifies present authority; it does
not make access to that person a prerequisite for a lawful technical successor.

## Durable project identity

The successor should preserve these contracts when technically possible:

| Contract                | Current value                                         | Fork rule                                                                                        |
| ----------------------- | ----------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| Project name            | Akashi                                                | Preserve the name and clearly identify the distribution as a continuation or fork.               |
| Repository              | `jbboehr/akashi.php`                                  | Preserve full Git history in a successor-controlled fork.                                        |
| Composer package        | `jbboehr/akashi`                                      | Use a new vendor/package identity unless control of the original package is validly transferred. |
| PHP namespace           | `jbboehr\Akashi`                                      | Preserve it for source and consumer compatibility; it need not match the new Composer vendor.    |
| CLI                     | `vendor/bin/akashi`                                   | Preserve the executable name and documented process contract.                                    |
| License                 | `AGPL-3.0-only WITH romic-exception`                  | Preserve all notices, license text, and source obligations.                                      |
| Documentation           | mdBook sources under `docs/pages/`                    | Publish from the fork under a successor-controlled URL.                                          |
| Behavioral constitution | invariants, conformance tests, compatibility fixtures | Treat these as the baseline before changing implementation.                                      |

The PHP namespace is a technical compatibility identifier. Preserving it does not authorize a successor to publish under
the original Composer account, impersonate the former steward, or claim control of the original repository.

## When to fork

Use the fork path when continued maintenance is needed and any of these conditions holds:

- the canonical repository is archived or no longer accepts necessary maintenance;
- no authorized maintainer can publish a required release;
- GitHub, Packagist, Pages, or signing access cannot be recovered promptly;
- ownership is disputed or a requested transfer cannot be independently verified; or
- the original steward is unavailable and waiting would expose users to an unfixed defect or ecosystem incompatibility.

Freeze releases under the original identity while authority or artifact provenance is uncertain. Read-only
investigation, mirroring, and verification may continue during the freeze.

## Fork recovery procedure

1. Mirror-clone the public repository with all branches, tags, and history. Record the source URL, the selected fork
   point, and its full commit hash.
2. Verify available signed tags and CI evidence. Choose the latest commit that passes `composer check`; also run
   `composer check:full` before the first successor release.
3. Push the complete history to a repository controlled by the successor. Protect the maintenance branch and require the
   repository's ordinary local gates in CI.
4. Retain the PHP namespace, public API categories, Markdown contracts, CLI name, license notices, and conformance
   fixtures unless a separately documented migration requires a change.
5. Change the Composer package name to a successor-controlled identity such as `<successor-vendor>/akashi`. Do not
   publish as `jbboehr/akashi` without actual Packagist authority, and do not declare that the fork `replace`s the
   original package by default.
6. Update repository, support, and documentation URLs to the fork. Keep the original fork point in the README and first
   successor release notes.
7. Publish documentation from successor-controlled infrastructure and perform the fresh-consumer verification in
   [`development/releasing.md`](development/releasing.md) using the new Composer identity.
8. Release a new version whose notes distinguish inherited Akashi behavior from successor changes. Never recreate or
   move an original signed tag.

Consumers migrate by changing only the Composer package requirement when the fork preserves the original PHP namespace
and public behavior. Any namespace or API migration must be explicit, versioned, and supported by compatibility tests.

## Optional ownership transfer

A valid transfer of the GitHub repository, Packagist package, or documentation site may allow the canonical identities
to continue, but the project must not rely on that outcome. Before accepting a transfer:

- verify the transfer through the service itself rather than an email or copied credential;
- record the old and new stewards and effective date in repository history;
- rotate automation credentials and signing keys instead of sharing private material;
- rerun both repository gates and artifact verification; and
- retain the fork procedure as the recovery path for any later loss of access.

If only some services transfer, use successor-controlled identities for the remainder and document the split plainly.

## External custody and secrets

Critical custody points are GitHub repository administration, the active Composer package, documentation hosting, and
the current release-signing identity. Recovery codes, tokens, private keys, and account credentials must remain outside
the repository. This document records required capabilities and recovery behavior, never secret values.

Akashi currently has one recorded steward, which is an acknowledged continuity risk. A future additional maintainer may
reduce that risk, but the public fork procedure remains authoritative even if no prearranged successor exists.

The agent-badge endpoint and its publishing state are optional presentation infrastructure. Losing them may remove a
badge; it must not block source verification, documentation builds, package creation, consumer installation, or a forked
release.
