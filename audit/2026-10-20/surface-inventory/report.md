# Inventaire des flags, scripts et chemins

Le 20 octobre 2026. Recensement de la surface active avant toute suppression.

## Flags CLI actifs

`--main`, `--autoload-path`, `--ffi-dir`, `--output`, `--rewrite-limit`,
`--bundle`, `--bundle-only`, `--verbose`, `--no-cache`, `--profile-build`,
`--profile-purmeta`, `--purmeta-cache-mib`.

Les options `--dce-exports` et `--no-dce-exports` ne sont **pas** actives ; elles
n’existent que dans les audits historiques de la DCE annulée. Aucun flag de
runtime partagé n’est exposé.

## Environnement de tests

`PHPURS_TEST_CLI`, `PHPURS_CLI_PROBE`, `PHPURS_PURMETA_PROBE`,
`PHPURS_TEST_CACHE_CONFIG`, `PHPURS_TEST_IO_ERROR`, `GOPURS_JOBS`.

Ils sont utilisés par les tests CLI/cache/profils actuels.

## Scripts et outils

- `npm run build` : `spago build` + `tools/bundle.mjs`.
- `npm run test:codegen` : racine + `cli/` + `ffi/`.
- `tools/bundle.mjs` : unique outil de packaging Node.
- `bin/phpurs.js` : produit ignoré, reconstruit par `prepare`.

Aucun script ou fichier source manifestement obsolète n’a été trouvé. Aucune
suppression n’est faite dans cette étape.

## Décision

La surface active est cohérente. Les seules mentions obsolètes sont
historiques et restent dans les audits. Les prochaines suppressions devront
être précédées d’un usages scan (scripts, docs, tests et CI).
