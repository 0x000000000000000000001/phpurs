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

    # Local backend source is a relative-path package in spago.lock; supply
    # the pinned fork so `spago build` can resolve it in the Nix sandbox.
    pbo = {
      url = "github:0x000000000000000000001/purescript-backend-optimizer/ef2fdae421ff998352a77407d861540d6b287928";
      flake = false;
    };

    # TAST compiler fork (same revision as gopurs): phpurs emits typed CoreFn.
    purs-tast = {
      url = "github:0x000000000000000000001/purescript/105f5752b39cc0f6c99a40064295338bf57ad223";
      flake = false;
    };
  };

  outputs = { self, nixpkgs, pbo, purs-tast, ... }@inputs:
    let
      supportedSystems = [ "x86_64-linux" "aarch64-linux" "aarch64-darwin" ];

      forAllSystems = nixpkgs.lib.genAttrs supportedSystems;

      nixpkgsFor = forAllSystems (system:
        import nixpkgs {
          inherit system;
          overlays = builtins.attrValues self.overlays;
        });

      # The backend consumes typed CoreFn: use the TAST fork, not upstream.
      pursFor = pkgs: import ./nix/purs-tast.nix { inherit pkgs; src = purs-tast; };
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

            preBuild = ''
              sed -i "s|path: '../../purescript-backend-optimizer-phpurs'|path: '${pbo}'|" spago.yaml
              sed -i "s|\"path\": \"../../purescript-backend-optimizer-phpurs\"|\"path\": \"${pbo}\"|" spago.lock
            '';

            nativeBuildInputs = [
              purs
              pkgs.spago-unstable
              pkgs.esbuild
              pkgs.makeWrapper
            ];

            # Mirror tools/bundle.mjs with the Nix esbuild: the npm wrapper
            # embeds the CJS bundle and installs a tiny launcher. This avoids
            # purs-backend-es output, which crashes on this compiler/Node pair.
            buildPhase = ''
              runHook preBuild
              spago build
              ${pkgs.esbuild}/bin/esbuild output/Main/index.js \
                --bundle --platform=node --format=cjs --outfile=phpurs-backend.cjs
              runHook postBuild
            '';

            installPhase = ''
              runHook preInstall
              mkdir -p $out/libexec/phpurs $out/bin
              cp phpurs-backend.cjs $out/libexec/phpurs/phpurs-backend.cjs
              cat > $out/libexec/phpurs/phpurs.js <<'EOF'
              #!/usr/bin/env node
              require('./phpurs-backend.cjs').main();
              EOF
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