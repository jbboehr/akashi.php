# Benchmarking

Akashi uses [PHPBench](https://phpbench.readthedocs.io/) to measure representative source extraction, transformation,
and PHPStan-verification workloads. Benchmarks live under `benchmarks/`; their inputs are deterministic in-memory
fixtures so ordinary runs do not depend on a checkout's documentation corpus or temporary filesystem performance.

## Running the suite

Run the complete timing suite from the repository root:

```console
composer benchmark
```

PHPBench performs warmup revolutions and multiple measured iterations, then reports aggregate timing and relative
standard deviation. Treat a high relative standard deviation as an instruction to rerun under quieter conditions, not as
evidence for an optimization.

Run one group while investigating a subsystem:

```console
composer benchmark -- --group=source
composer benchmark -- --group=transform
composer benchmark -- --group=phpstan
```

The smoke command reduces every subject to one warmup and one measured revolution:

```console
composer benchmark:smoke
```

It validates benchmark discovery, fixture construction, and execution. The Nix flake exposes smoke runs on PHP 8.1 and
8.2 as independent routine checks; neither imposes a timing threshold.

## Hardware performance counters

On Linux, the default Nix development shell builds and loads
[`php-perfidious`](https://github.com/jbboehr/php-perfidious). The
[`phpbench-perfidious`](https://github.com/jbboehr/phpbench-perfidious) adapter supplies a separate PHPBench executor
and report for Linux `perf_events` counters:

```console
nix develop
composer benchmark:perf
composer benchmark:perf -- --group=transform
```

The configured profile records CPU clock, retired instructions, page faults, and context switches. Use the isolated
executor when cross-subject process state would distort a comparison:

```console
composer benchmark:perf -- --profile=perfidious-remote
```

The adapter is a Composer development dependency, but normal benchmarks do not load it and do not require
`ext-perfidious`. Counter-backed runs require Linux, the extension, and host permission to use the requested events.
Hardware counters are intentionally absent from CI because hosted virtualization may not expose them.

Counter values include PHPBench executor and dynamic-call overhead. Compare equivalent subjects on the same host; do not
interpret them as exact counts for an isolated expression.

## Comparing a change

Store a tagged timing run before modifying an implementation:

```console
composer benchmark -- --store --tag=before_change
```

Run the candidate against that local reference:

```console
composer benchmark -- --ref=before_change
```

PHPBench stores local results under `.phpbench/`, which is ignored. Results depend on the PHP version, extensions, INI
configuration, CPU scaling, system load, and operating system; machine-specific measurements are not committed as
project-wide guarantees.

## Interpreting the initial subjects

Source subjects compare small and large Markdown extraction, PHPDoc extraction, and corpus invariant validation.
Transformation subjects exercise the complete in-process preparation pipeline with both a small assertion and a
declaration-heavy example. PHPStan subjects isolate expectation parsing, JSON decoding, exact diagnostic matching, and a
deliberately dense compatible matching graph.

Fixture construction runs outside each measured subject. Subprocess execution, external PHPStan launches, formatter
processes, and filesystem synchronization are not mixed into these microbenchmarks because startup and host I/O would
dominate their internal work. Add separate end-to-end benchmarks if those workflows become optimization targets.

Use measurements to identify a material cost before changing public behavior, cache ownership, or safety checks. A
faster microbenchmark is not sufficient when its operation does not contribute meaningfully to a real documentation
verification run.
