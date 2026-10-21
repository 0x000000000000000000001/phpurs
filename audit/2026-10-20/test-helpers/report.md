# Helper de fixtures temporaires

Le 20 octobre 2026. Début de la factorisation des helpers de tests codegen.

## Résultat

- `tests/codegen/fixtures/cli.mjs` fournit `temporaryRoot`, `runPhp` et
  `snapshotFiles`.
- `temporaryRoot` crée un répertoire réel et le supprime automatiquement.
- `runPhp` centralise les flags PHP par défaut, le cwd, le timeout et
  l’environnement.
- `snapshotFiles` centralise les snapshots de sorties PHP/Composer/manifeste.
- `cache-cli.mjs`, `composer-merge.mjs`, `file-emission.mjs`, `build-profile.mjs`,
  `purmeta-budget.mjs`, `purmeta-profile.mjs`, `rewrite-limit.mjs` et
  `module-cache.mjs` sont migrés.
- `snapshotText` remplace le snapshot récursif local de `file-emission`.

## Vérification

- `cache-cli.mjs` : **19/19** ; `file-emission.mjs` : **8/8** ;
  profils/options : **17/17** ; `module-cache` : **5/5** ;
  `composer-merge.mjs` : OK.
- Suite complète : **102/102**.
- Aucun code du compilateur modifié.

Prochaines cibles : helpers de lancement CLI, d’exécution PHP et de snapshot de
sorties, puis migration progressive des tests les plus dupliqués.
