# Mutation testing

Akashi uses Infection for mutation testing once behavior-bearing source is present.

```shell
composer infection
```

Use escaped mutants to identify assertions that do not constrain observable behavior. Generated reports and logs are
ignored by Git.

The deliberate pre-0.1 classification of accepted survivors is recorded in
[the mutation survivor review](mutation-review.md).
