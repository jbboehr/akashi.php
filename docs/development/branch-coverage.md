# Branch coverage

Branch and path coverage require Xdebug. Enter the dedicated development shell and run:

```shell
nix develop .#xdebug
composer coverage:branch
```

Reports are written beneath `coverage/branch`.
