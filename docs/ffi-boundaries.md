# Frontières FFI JavaScript / PureScript

Ce document décrit les modules JavaScript appelés par le compilateur. Les
frontières doivent rester minces : conversion de données, accès système et
opérations qui ne sont pas exprimables raisonnablement en PureScript.

## Principes

- Les valeurs PureScript passent par des fonctions curryfiées ; les callbacks
  ne doivent pas être stockés au-delà de l’appel sans contrat explicite.
- Les erreurs de validation sont renvoyées en `Either` quand la signature le
  permet ; les erreurs d’E/S et les exceptions inattendues remontent à l’appelant.
- Les buffers et structures sérialisées sont traités comme des octets, jamais
  comme des objets mutables partagés.
- Aucun type interne du TAST/PBO n’est exposé comme API PHP ; le cache sérialise
  uniquement un graphe de données validé.

## Modules

| Module JS | Wrapper | Entrées / sorties | Erreurs |
| --- | --- | --- | --- |
| `ComposerMerge.js` | `Phpurs.ComposerMerge` | Racines Composer, callback de lecture, résultat fusionné | Erreurs d’E/S et de parsing propagées |
| `BuildInputs.js` | `Phpurs.BuildInputs` | Chemins, concurrence, test de fichier absent | Aucune validation métier |
| `BuildProfile.js` | `Phpurs.BuildProfile` | Profile, spans, phases, JSON | Une action qui échoue termine le span en échec puis relance |
| `CacheKey.js` | `Phpurs.CacheKey` | Contexte, inputs, dépendances, clés | `Either String` pour cwd/input/dépendance invalides |
| `FileEmission.js` | `Phpurs.FileEmission` | Chemin, test d’absence | Erreurs FS propagées |
| `FreeVars.js` | `Phpurs.FreeVars` | Mémoïsation de graphes TAST | Les erreurs du callback remontent |
| `Metrics.js` | `Phpurs.Metrics` | Horloge monotone | Aucune |
| `ModuleCache.js` | `Phpurs.ModuleCache` | États de modules, serialisation V8, checksums | `throw` sur graphe/champs/valeurs non valides ; erreurs FS propagées |
| `OutputManifest.js` | `Phpurs.OutputManifest` | Suivi des sorties, manifeste, cleanup | Entrées invalides ignorées ; erreurs FS propagées |
| `PackagePaths.js` | `Phpurs.PackagePaths` | Répertoires Spago/FFI, chemins candidats | Erreurs FS éventuelles remontent |
| `Printer/Expr.js` | `Phpurs.Printer.Expr` | Noms PHP, échappement, entiers 32 bits | Pure, aucune erreur |
| `PurmetaBudget.js` | `Phpurs.PurmetaBudget` | Budget mémoire PBO | `Either String` pour valeur absente/invalide |
| `RewriteLimit.js` | `Phpurs.RewriteLimit` | Limite de réécriture | `Either String` pour valeur absente/invalide |

## Couverture des erreurs

Les chemins d’erreur sont principalement couverts par :

- `tests/codegen/cache-key.mjs` et `cache-cli.mjs` pour les clés, manifestes,
  caches corrompus et nettoyage ;
- `tests/codegen/build-profile.mjs` pour les spans, phases et échecs ;
- `tests/codegen/purmeta-budget.mjs` et `rewrite-limit.mjs` pour les options ;
- `tests/codegen/missing-ffi.mjs` pour les erreurs d’exports PHP ;
- `tests/codegen/composer-merge.mjs` pour les manifestes Composer malformés ou absents ;
- `tests/codegen/runtime-preamble.mjs` et `printer` pour les helpers purs.

Toute nouvelle exportation FFI doit indiquer ici son entrée, sa sortie, son
propriétaire de données et son comportement en erreur.
