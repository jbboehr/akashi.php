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
in
buildNpmPackage rec {
  pname = "agent-badge-unwrapped";
  version = "1.1.20";

  src = fetchFromGitHub {
    owner = "arlegotin";
    repo = "agent-badge";
    rev = "d209a47a128eb1451002cacfa4030466c9833e92";
    hash = "sha256-qfhoo6UEm+TASHriuVg4ZmQKwACIDBMhS8s/YlKT3cI=";
  };

  patches = [ ./patches/agent-badge-inline-readme-markers.patch ];

  npmDepsHash = "sha256-M5eCFQWPgsZJxrKXkB7ChJcgHZHgQL9rIeSD7Aa2Yko=";

  nodejs = nodejs_24;
  npmFlags = [ "--ignore-scripts" ];
  npmBuildScript = "build";

  postPatch = ''
    for source_file in $(grep -rl --include='*.ts' --fixed-strings '.agent-badge' packages); do
      substituteInPlace "$source_file" \
        --replace-fail '.agent-badge' '${agentBadgeDirectory}'
    done

    substituteInPlace packages/core/src/init/runtime-wiring.test.ts \
      --replace-fail '\.github/agent-badge' '\.github\/agent-badge' \
      --replace-fail 'scripts.github/agent-badge:' 'scripts.agent-badge:'
  '';

  nativeBuildInputs = [ makeWrapper ];
  nativeCheckInputs = [ gitMinimal ];

  doCheck = true;
  checkPhase = ''
    runHook preCheck

    npm test -- --run \
      packages/core/src/init/scaffold.test.ts \
      packages/core/src/init/preflight.test.ts \
      packages/core/src/init/runtime-wiring.test.ts \
      packages/core/src/diagnostics/doctor.test.ts \
      packages/core/src/publish/readme-badge.test.ts \
      packages/core/src/scan/refresh-cache.test.ts \
      packages/agent-badge/src/commands/config.test.ts \
      packages/agent-badge/src/commands/doctor.test.ts \
      packages/agent-badge/src/commands/status.test.ts \
      packages/agent-badge/src/commands/uninstall.test.ts
    test "$(node packages/agent-badge/dist/cli/main.js --version)" = "${version}"

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
    homepage = "https://github.com/arlegotin/agent-badge";
    license = lib.licenses.mit;
    mainProgram = "agent-badge";
    platforms = lib.platforms.unix;
  };

  passthru = { inherit agentBadgeDirectory; };
}
