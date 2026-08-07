{
  lib,
  buildNpmPackage,
  fetchFromGitHub,
  gitMinimal,
  makeWrapper,
  nodejs_24,
}:

let
  agentBadgeDirectory = ".github/agent-badge";
  cliVersion = "1.1.20";
in
buildNpmPackage rec {
  pname = "agent-badge-unwrapped";
  version = "${cliVersion}-unstable-2026-08-07";

  src = fetchFromGitHub {
    owner = "jbboehr";
    repo = "agent-badge.ts";
    rev = "819aed1665850b7b5015d89936b8745382ec321f";
    hash = "sha256-8WJr1YkwsU7dsUVZiW4WwLF2mkugz5zqIvQAtYxZdto=";
  };

  npmDepsHash = "sha256-M5eCFQWPgsZJxrKXkB7ChJcgHZHgQL9rIeSD7Aa2Yko=";

  nodejs = nodejs_24;
  npmFlags = [ "--ignore-scripts" ];
  npmBuildScript = "build";

  nativeBuildInputs = [ makeWrapper ];
  nativeCheckInputs = [ gitMinimal ];

  doCheck = true;
  checkPhase = ''
    runHook preCheck

    # These four test files use the dev-only better-sqlite3 fixture. Keep npm
    # install scripts disabled and run every test that does not need its native
    # addon; agent-badge itself uses Node's built-in SQLite support.
    npm test -- --run \
      --exclude packages/agent-badge/src/commands/scan.test.ts \
      --exclude packages/core/src/pricing/estimate-cost.test.ts \
      --exclude packages/core/src/providers/codex/codex-adapter.test.ts \
      --exclude packages/core/src/scan/full-backfill.test.ts
    test "$(node packages/agent-badge/dist/cli/main.js --version)" = "${cliVersion}"

    runHook postCheck
  '';

  installPhase = ''
    runHook preInstall

    npm prune --omit=dev --ignore-scripts
    test -e node_modules/@legotin/agent-badge-core

    mkdir -p "$out/bin" "$out/libexec/agent-badge"
    cp -r node_modules packages "$out/libexec/agent-badge/"

    makeWrapper ${lib.getExe nodejs_24} "$out/bin/agent-badge" \
      --add-flags "$out/libexec/agent-badge/packages/agent-badge/dist/cli/main.js"

    runHook postInstall
  '';

  meta = {
    description = "Unwrapped agent-badge CLI";
    homepage = "https://github.com/jbboehr/agent-badge.ts";
    license = lib.licenses.mit;
    mainProgram = "agent-badge";
    platforms = lib.platforms.unix;
  };

  passthru = { inherit agentBadgeDirectory; };
}
