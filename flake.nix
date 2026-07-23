{
  description = "phpurs, a PureScript to PHP compiler backend";

  inputs = {
    nixpkgs.url = "github:nixos/nixpkgs/nixpkgs-unstable";

    # Only used by shell.nix for non-flake users.
    flake-compat = {
      url = "github:edolstra/flake-compat";
      flake = false;
    };

    # Canonical PureScript toolchain overlay (purs, spago, purs-tidy,
    # purs-backend-es, purescript-language-server).
    purescript-overlay = {
      url = "github:thomashoneyman/purescript-overlay";
      inputs.nixpkgs.follows = "nixpkgs";
    };

    # Pure `nix build` for spago projects, driven entirely by spago.lock.
    # Handles both registry packages (integrity hashes in the lock) and the
    # git-pinned backend-optimizer fork (locked rev).
    mkSpagoDerivation = {
      url = "github:jeslie0/mkSpagoDerivation";
      inputs.nixpkgs.follows = "nixpkgs";
      inputs.ps-overlay.follows = "purescript-overlay";
    };
  };

  outputs = { self, nixpkgs, ... }@inputs:
    let
      supportedSystems = [ "x86_64-linux" "aarch64-linux" "aarch64-darwin" ];

      forAllSystems = nixpkgs.lib.genAttrs supportedSystems;

      nixpkgsFor = forAllSystems (system:
        import nixpkgs {
          inherit system;
          overlays = builtins.attrValues self.overlays;
        });

      # spago.lock declares the compiler bound `>=0.15.15 <0.16.0`, so pin the
      # compiler explicitly instead of floating on the overlay's `purs`.
      pursFor = pkgs: pkgs.purs-bin.purs-0_15_15;
    in
    {
      overlays = {
        purescript = inputs.purescript-overlay.overlays.default;
        spago = inputs.mkSpagoDerivation.overlays.default;
      };

      packages = forAllSystems (system:
        let
          pkgs = nixpkgsFor.${system};
          purs = pursFor pkgs;

          phpurs = pkgs.mkSpagoDerivation {
            pname = "phpurs";
            version = "0.1.0";
            src = ./.;
            spagoYaml = ./spago.yaml;
            spagoLock = ./spago.lock;

            nativeBuildInputs = [
              purs
              pkgs.spago-unstable
              pkgs.purs-backend-es
              pkgs.esbuild
              pkgs.makeWrapper
            ];

            # Mirrors the npm "build" script, minus npm/npx. purs-backend-es
            # reads corefn from output/ and shells out to esbuild from PATH.
            buildPhase = ''
              runHook preBuild
              spago build
              purs-backend-es bundle-app --platform node --main Main --to bin/phpurs.js
              runHook postBuild
            '';

            # nodejs-slim: node runtime only, no npm/npx/corepack. The bundle
            # is fully self-contained so the bare interpreter is all we need,
            # and it keeps the runtime closure of `nix run` minimal.
            installPhase = ''
              runHook preInstall
              mkdir -p $out/libexec/phpurs $out/bin
              cp bin/phpurs.js $out/libexec/phpurs/phpurs.js
              makeWrapper ${pkgs.nodejs-slim}/bin/node $out/bin/phpurs \
                --add-flags "$out/libexec/phpurs/phpurs.js"
              runHook postInstall
            '';

            meta = {
              description = "PureScript compiler backend targeting PHP";
              homepage = "https://github.com/0x000000000000000000001/phpurs";
              mainProgram = "phpurs";
            };
          };
        in
        {
          inherit phpurs;
          default = phpurs;
        });

      apps = forAllSystems (system:
        let
          app = {
            type = "app";
            program = "${self.packages.${system}.default}/bin/phpurs";
            meta.description = "PureScript compiler backend targeting PHP";
          };
        in
        {
          phpurs = app;
          default = app;
        });

      checks = forAllSystems (system:
        let
          pkgs = nixpkgsFor.${system};
        in
        {
          build = self.packages.${system}.default;

          # The compiler run outside a spago project must fail gracefully
          # rather than crash the wrapper: nonzero exit, complaining about
          # the missing output/ directory. This asserts the node wrapper,
          # the slim runtime, and the bundle are all wired correctly.
          smoke = pkgs.runCommand "phpurs-smoke" { } ''
            if ${self.packages.${system}.default}/bin/phpurs 2> err.log; then
              echo "expected nonzero exit outside a project" >&2
              exit 1
            fi
            grep -q "output" err.log
            touch $out
          '';
        });

      devShells = forAllSystems (system:
        let
          pkgs = nixpkgsFor.${system};
          purs = pursFor pkgs;
        in
        {
          default = pkgs.mkShell {
            name = "phpurs";
            packages = [
              # PureScript toolchain
              purs
              pkgs.spago-unstable
              pkgs.purs-backend-es
              pkgs.purs-tidy
              pkgs.purescript-language-server

              # Bundling and running the compiler itself. Full nodejs here on
              # purpose: bin/test invokes `npm run build`, which nodejs-slim
              # does not provide.
              pkgs.esbuild
              pkgs.nodejs

              # Running compiled output and the test suite (needs PHP >= 8.1
              # for Fibers; revolt/event-loop comes in per-project via composer)
              pkgs.php
              pkgs.phpPackages.composer

              pkgs.nixpkgs-fmt
            ];
          };
        });

      formatter = forAllSystems (system: nixpkgsFor.${system}.nixpkgs-fmt);
    };
}