{
  description = "jbboehr/akashi";

  inputs = {
    nixpkgs.url = "github:nixos/nixpkgs/nixos-26.05";
    flake-utils.url = "github:numtide/flake-utils";
    pre-commit-hooks = {
      url = "github:cachix/pre-commit-hooks.nix";
      inputs.nixpkgs.follows = "nixpkgs";
    };
    treefmt-nix = {
      url = "github:numtide/treefmt-nix";
      inputs.nixpkgs.follows = "nixpkgs";
    };
    gitignore = {
      url = "github:hercules-ci/gitignore.nix";
      inputs.nixpkgs.follows = "nixpkgs";
    };
    phps.url = "github:fossar/nix-phps";
    nix-github-actions = {
      url = "github:nix-community/nix-github-actions";
      inputs.nixpkgs.follows = "nixpkgs";
    };
  };

  outputs =
    {
      self,
      nixpkgs,
      flake-utils,
      pre-commit-hooks,
      treefmt-nix,
      gitignore,
      phps,
      nix-github-actions,
    }:
    flake-utils.lib.eachDefaultSystem (
      system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
        php-unwrapped = pkgs.php82;
        php81-unwrapped = phps.packages.${system}.php81;
        php81 = php81-unwrapped.buildEnv {
          extraConfig = "memory_limit = 2G";
        };
        php83 = pkgs.php83.buildEnv {
          extraConfig = "memory_limit = 2G";
        };
        php84 = pkgs.php84.buildEnv {
          extraConfig = "memory_limit = 2G";
        };
        php85 = pkgs.php85.buildEnv {
          extraConfig = "memory_limit = 2G";
        };
        php = php-unwrapped.buildEnv {
          extraConfig = "memory_limit = 2G";
          extensions =
            {
              enabled,
              all,
            }:
            enabled ++ [ all.pcov ];
        };
        php-xdebug = php-unwrapped.buildEnv {
          extraConfig = ''
            memory_limit = 2G
            xdebug.mode = off
          '';
          extensions =
            {
              enabled,
              all,
            }:
            enabled ++ [ all.xdebug ];
        };
        src = gitignore.lib.gitignoreSource ./.;
        php-checks = import ./nix/php-checks.nix {
          inherit
            pkgs
            src
            php81
            php83
            php84
            php85
            ;
          inherit (pkgs) lib;
          php82 = php;
        };
        agent-badge-unwrapped = pkgs.callPackage ./nix/agent-badge-unwrapped.nix { };
        agent-badge =
          if pkgs.stdenv.isLinux then
            pkgs.callPackage ./nix/agent-badge.nix { inherit agent-badge-unwrapped; }
          else
            null;
        treefmt = treefmt-nix.lib.evalModule pkgs {
          projectRootFile = "flake.nix";
          settings.global.excludes = [
            ".github/agent-badge/config.json"
            "docs/IMPLEMENTATION_HANDOFF.md"
            "docs/pages/assets/heliogenesis/**"
          ];
          programs.nixfmt = {
            enable = true;
            package = pkgs.nixfmt;
          };
          programs.prettier = {
            enable = true;
            settings = {
              proseWrap = "always";
              printWidth = 120;
              overrides = [
                {
                  files = "LICENSE.md";
                  options.proseWrap = "preserve";
                }
              ];
            };
          };
        };
        pre-commit-check = pre-commit-hooks.lib.${system}.run {
          inherit src;
          hooks = {
            actionlint.enable = true;
            shellcheck.enable = true;
            treefmt = {
              enable = true;
              package = treefmt.config.build.wrapper;
              require_serial = true;
            };
          };
        };
        mkDevShell =
          phpPackage:
          pkgs.mkShell {
            buildInputs = with pkgs; [
              actionlint
              agent-badge
              mdbook
              phpPackage
              phpPackage.packages.composer
              pre-commit
              treefmt.config.build.wrapper
            ];
            shellHook = ''
              ${pre-commit-check.shellHook}
              export PATH="$PWD/vendor/bin:$PATH"
            '';
          };
        github-actions-matrix =
          let
            normal = nix-github-actions.lib.mkGithubMatrix {
              checks = {
                inherit (self.checks) x86_64-linux;
              };
              attrPrefix = "checks";
              platforms.x86_64-linux = "ubuntu-24.04";
            };
            mutation = nix-github-actions.lib.mkGithubMatrix {
              checks.x86_64-linux = {
                inherit (self.packages.x86_64-linux) mutation;
              };
              attrPrefix = "packages";
              platforms.x86_64-linux = "ubuntu-24.04";
            };
          in
          pkgs.writeText "akashi-github-actions-matrix.json" (
            builtins.toJSON {
              include = normal.matrix.include ++ mutation.matrix.include;
            }
          );
      in
      rec {
        checks = {
          inherit pre-commit-check;
          inherit agent-badge-unwrapped;
          documentation =
            pkgs.runCommand "akashi-documentation"
              {
                nativeBuildInputs = [ pkgs.mdbook ];
              }
              ''
                mdbook build ${src}/docs --dest-dir "$out"
              '';
          formatting = treefmt.config.build.check self;
        }
        // php-checks.checks
        // pkgs.lib.optionalAttrs pkgs.stdenv.isLinux { inherit agent-badge; };
        devShells = {
          default = mkDevShell php;
          php81 = mkDevShell php81;
          xdebug = mkDevShell php-xdebug;
        };
        packages = {
          inherit agent-badge-unwrapped;
          mutation = php-checks.mutation;
        }
        // pkgs.lib.optionalAttrs pkgs.stdenv.isLinux {
          inherit agent-badge github-actions-matrix;
        };
        formatter = treefmt.config.build.wrapper;
      }
    );
}
