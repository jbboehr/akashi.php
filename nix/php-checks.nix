{
  lib,
  pkgs,
  src,
  php81,
  php82,
  php83,
  php84,
  php85,
}:

let
  projectVersion = "0.2.0";

  mkComposerSource =
    name: manifest: lock: rootBins:
    pkgs.runCommand "akashi-${name}-composer-source" { } ''
      mkdir -p "$out"
      cp ${manifest} "$out/composer.json"
      cp ${lock} "$out/composer.lock"
      ${lib.concatMapStringsSep "\n" (bin: ''
        mkdir -p "$(dirname "$out/${lib.escapeShellArg bin}")"
        touch "$out/${lib.escapeShellArg bin}"
        chmod +x "$out/${lib.escapeShellArg bin}"
      '') rootBins}
    '';

  mkComposerClosure =
    {
      name,
      php,
      manifest,
      lock,
      vendorHash,
      rootBins ? [ ],
    }:
    let
      inputDigest = builtins.substring 0 16 (
        builtins.hashString "sha256" (
          builtins.toJSON {
            manifest = builtins.hashFile "sha256" manifest;
            lock = builtins.hashFile "sha256" lock;
          }
        )
      );
      pname = "akashi-${name}-${inputDigest}-dependencies";
      composerSource = mkComposerSource name manifest lock rootBins;
      repository = php.mkComposerRepository {
        inherit pname vendorHash;
        version = projectVersion;
        src = composerSource;
        composerNoDev = false;
        composerNoPlugins = false;
        composerNoScripts = true;
      };
      dependencies = pkgs.stdenvNoCC.mkDerivation {
        inherit pname;
        version = projectVersion;
        src = composerSource;
        buildInputs = [ php ];
        nativeBuildInputs = [
          php.packages.composer-local-repo-plugin
          php.composerHooks.composerInstallHook
        ];
        composerRepository = repository;
        composerNoDev = false;
        composerNoPlugins = false;
        composerNoScripts = true;
        COMPOSER_DISABLE_NETWORK = 1;
        dontPatchShebangs = true;
      };
    in
    {
      inherit dependencies repository;
      vendor = "${dependencies}/share/php/${pname}/vendor";
    };

  normalClosure = mkComposerClosure {
    name = "normal";
    php = php82;
    manifest = ../composer.json;
    lock = ../composer.lock;
    rootBins = [ "bin/akashi" ];
    vendorHash = "sha256-9XrOQ+2d8Cg+obXT9hb80ZNokDZYtgbQGcv0C93oEew=";
  };

  php81Closure = mkComposerClosure {
    name = "php81";
    php = php81;
    manifest = ../composer.json;
    lock = ./composer/php81/composer.lock;
    rootBins = [ "bin/akashi" ];
    vendorHash = "sha256-idnHD4+Z7IiZi8JYszSDAo7iOaSNSyYQrqDBYSo7ZXI=";
  };

  lowestClosure = mkComposerClosure {
    name = "lowest";
    php = php81;
    manifest = ../composer.json;
    lock = ./composer/lowest/composer.lock;
    rootBins = [ "bin/akashi" ];
    vendorHash = "sha256-mh39nu5vAWA2amVnP9aLNWW9tZIcxoCxcTLPITFWkt0=";
  };

  phpstan1Closure = mkComposerClosure {
    name = "phpstan1";
    php = php81;
    manifest = ./composer/phpstan1/composer.json;
    lock = ./composer/phpstan1/composer.lock;
    vendorHash = "sha256-LGhk5v2i+TUqdQmwVkgigw0zIGpGFGIXt8SUSrv/BnE=";
  };

  mkPhpCheck =
    {
      name,
      php,
      command,
      closure ? normalClosure,
      composer ? null,
      nativeBuildInputs ? [ ],
    }:
    pkgs.stdenvNoCC.mkDerivation {
      pname = "akashi-check-${name}";
      version = projectVersion;
      inherit src;
      nativeBuildInputs = [
        php
      ]
      ++ lib.optional (composer != null) composer
      ++ nativeBuildInputs;
      dontConfigure = true;
      buildPhase = ''
        runHook preBuild

        mkdir -p vendor "$TMPDIR/home" "$TMPDIR/cache"
        cp -R ${closure.vendor}/. vendor/
        chmod -R u+w vendor
        patchShebangs vendor/bin
        export HOME="$TMPDIR/home"
        export XDG_CACHE_HOME="$TMPDIR/cache"
        export COMPOSER_CACHE_DIR="$TMPDIR/cache/composer"
        export PATH="$PWD/vendor/bin:$PATH"

        ${command}

        runHook postBuild
      '';
      installPhase = ''
        runHook preInstall

        mkdir -p "$out"
        touch "$out/passed"

        runHook postInstall
      '';
    };

  phpunitCheck =
    name: php: closure:
    mkPhpCheck {
      inherit name php closure;
      command = "php vendor/bin/phpunit --colors=never --no-coverage";
    };
in
{
  checks = {
    phpunit-php81 = phpunitCheck "phpunit-php81" php81 php81Closure;
    phpunit-php82 = phpunitCheck "phpunit-php82" php82 normalClosure;
    phpunit-php83 = phpunitCheck "phpunit-php83" php83 normalClosure;
    phpunit-php84 = phpunitCheck "phpunit-php84" php84 normalClosure;
    phpunit-php85 = phpunitCheck "phpunit-php85" php85 normalClosure;

    phpunit-reverse-order = mkPhpCheck {
      name = "phpunit-reverse-order";
      php = php82;
      command = "php vendor/bin/phpunit --colors=never --no-coverage --order-by=reverse";
    };

    phpstan = mkPhpCheck {
      name = "phpstan";
      php = php82;
      command = "php vendor/bin/phpstan analyse --no-progress --error-format=raw";
    };

    php-cs-fixer = mkPhpCheck {
      name = "php-cs-fixer";
      php = php82;
      command = "php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff --using-cache=no";
    };

    composer-validate = mkPhpCheck {
      name = "composer-validate";
      php = php82;
      composer = php82.packages.composer;
      command = "composer validate --strict";
    };

    composer-locks-validate =
      pkgs.runCommand "akashi-composer-locks-validate"
        {
          nativeBuildInputs = [
            php81
            php81.packages.composer
          ];
        }
        ''
          validateLock() {
            local name=$1
            local manifest=$2
            local lock=$3
            local directory="$TMPDIR/$name"

            mkdir -p "$directory"
            cp "$manifest" "$directory/composer.json"
            cp "$lock" "$directory/composer.lock"
            chmod -R u+w "$directory"
            composer validate --strict --no-interaction --working-dir="$directory"
          }

          validateConsumerLock() {
            local name=$1
            local manifest=$2
            local lock=$3
            local root="$TMPDIR/$name"
            local directory="$root/consumer"

            mkdir -p "$directory" "$root/package"
            cp "$manifest" "$directory/composer.json"
            cp "$lock" "$directory/composer.lock"
            cp ${../composer.json} "$root/package/composer.json"
            chmod -R u+w "$root"
            composer config \
              --working-dir="$directory" \
              repositories.akashi \
              '{"type":"path","url":"../package","options":{"reference":"none","symlink":false,"versions":{"jbboehr/akashi":"dev-compatibility"}}}' \
              --no-interaction
            composer validate --strict --no-interaction --working-dir="$directory"
            php ${../tests/Compatibility/assert-package-lock-current.php} \
              "$root/package/composer.json" \
              "$directory/composer.lock"
          }

          validateLock php81 ${../composer.json} ${./composer/php81/composer.lock}
          validateLock lowest ${../composer.json} ${./composer/lowest/composer.lock}
          validateLock phpstan1 ${./composer/phpstan1/composer.json} ${./composer/phpstan1/composer.lock}
          validateConsumerLock \
            consumer-phpunit10 \
            ${../tests/Compatibility/PHPUnit10/fixture/composer.json} \
            ${./composer/consumer-phpunit10/composer.lock}
          validateConsumerLock \
            consumer-phpstan1 \
            ${../tests/Compatibility/PHPStan1/fixture/composer.json} \
            ${./composer/consumer-phpstan1/composer.lock}

          touch "$out"
        '';

    php-lint-php81 = pkgs.runCommand "akashi-php-lint-php81" { nativeBuildInputs = [ php81 ]; } ''
      {
        find ${src}/benchmarks ${src}/src ${src}/tests ${src}/tools -type f -name '*.php' -print0
        printf '%s\0' ${src}/bin/akashi
      } | xargs -0 --no-run-if-empty -n 1 php -l
      touch "$out"
    '';

    benchmark-smoke-php81 = mkPhpCheck {
      name = "benchmark-smoke-php81";
      php = php81;
      closure = php81Closure;
      command = "php vendor/bin/phpbench run --iterations=1 --revs=1 --warmup=1 --progress=none";
    };

    benchmark-smoke-php82 = mkPhpCheck {
      name = "benchmark-smoke-php82";
      php = php82;
      command = "php vendor/bin/phpbench run --iterations=1 --revs=1 --warmup=1 --progress=none";
    };

    package = mkPhpCheck {
      name = "package";
      php = php82;
      composer = php82.packages.composer;
      command = "php tools/check-package.php";
    };

    lowest-dependencies-php81 = mkPhpCheck {
      name = "lowest-dependencies-php81";
      php = php81;
      closure = lowestClosure;
      command = ''
        php vendor/bin/phpstan analyse --no-progress --error-format=raw
        php vendor/bin/phpunit --colors=never --no-coverage
      '';
    };

    consumer-phpunit10 = mkPhpCheck {
      name = "consumer-phpunit10";
      php = php81;
      composer = php81.packages.composer-local-repo-plugin;
      closure = php81Closure;
      command = ''
        AKASHI_COMPOSER_REPOSITORY=${php81Closure.repository} \
          AKASHI_COMPOSER_LOCK=${./composer/consumer-phpunit10/composer.lock} \
          bash tests/Compatibility/PHPUnit10/run
      '';
    };

    consumer-phpstan1 = mkPhpCheck {
      name = "consumer-phpstan1";
      php = php81;
      composer = php81.packages.composer-local-repo-plugin;
      closure = php81Closure;
      command = ''
        AKASHI_COMPOSER_REPOSITORY=${phpstan1Closure.repository} \
          AKASHI_COMPOSER_LOCK=${./composer/consumer-phpstan1/composer.lock} \
          bash tests/Compatibility/PHPStan1/run
      '';
    };

    paratest-cases = mkPhpCheck {
      name = "paratest-cases";
      php = php82;
      command = "php vendor/bin/paratest --processes=2 --colors=never --no-coverage";
    };

    paratest-functional = mkPhpCheck {
      name = "paratest-functional";
      php = php82;
      command = "php vendor/bin/paratest --processes=2 --functional --colors=never --no-coverage";
    };
  };

  mutation = mkPhpCheck {
    name = "mutation";
    php = php82;
    command = ''
      substituteInPlace phpunit.xml.dist \
        --replace-fail \
          'https://schema.phpunit.de/11.5/phpunit.xsd' \
          "file://$PWD/vendor/phpunit/phpunit/phpunit.xsd"
      php vendor/bin/infection --no-progress --min-msi=90 --min-covered-msi=90
    '';
  };

  inherit
    normalClosure
    php81Closure
    lowestClosure
    phpstan1Closure
    ;
}
