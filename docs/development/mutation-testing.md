# Mutation testing

Akashi uses Infection for mutation testing. The reproducible mutation target is explicit because it is substantially
more expensive than the routine local gate:

```shell
nix build .#mutation -L
```

Mutation testing is included in the generated exhaustive Nix CI matrix, but it is intentionally absent from
`nix flake check --keep-going -L`. Use `composer infection` when an editable Composer checkout or Infection's
interactive output is more useful during focused development. Both routes retain the thresholds and behavior in
`infection.json5.dist`.

Use escaped mutants to identify assertions that do not constrain observable behavior. Do not lower thresholds, exclude
important code, or assert incidental internals merely to change the score. Generated reports and logs are ignored by
Git.

The current point-in-time classification of accepted survivors is recorded in
[the mutation survivor review](mutation-review.md).
