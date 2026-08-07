{
  lib,
  buildNpmPackage,
  fetchFromGitHub,
  makeWrapper,
  nodejs_24,
}:

buildNpmPackage rec {
  pname = "agent-badge-unwrapped";
  version = "1.1.20";

  src = fetchFromGitHub {
    owner = "arlegotin";
    repo = "agent-badge";
    rev = "d209a47a128eb1451002cacfa4030466c9833e92";
    hash = "sha256-qfhoo6UEm+TASHriuVg4ZmQKwACIDBMhS8s/YlKT3cI=";
  };

  npmDepsHash = "sha256-M5eCFQWPgsZJxrKXkB7ChJcgHZHgQL9rIeSD7Aa2Yko=";

  nodejs = nodejs_24;
  npmFlags = [ "--ignore-scripts" ];
  npmBuildScript = "build";

  nativeBuildInputs = [ makeWrapper ];

  doCheck = true;
  checkPhase = ''
    runHook preCheck

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
}
