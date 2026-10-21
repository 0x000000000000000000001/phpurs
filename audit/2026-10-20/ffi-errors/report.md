# Couverture des erreurs FFI

Le 20 octobre 2026. Inventaire des chemins d’erreur des modules JavaScript FFI
et ajout du test manquant pour `ComposerMerge`.

## Couverture

- `ComposerMerge` : **nouveau test** `composer-merge.mjs` (racines ordonnées,
  manifeste malformé, racine absente, précédence require/require-dev).
- `BuildProfile`, `CacheKey`, `FileEmission`, `ModuleCache`, `OutputManifest`,
  `PurmetaBudget`, `RewriteLimit` : chemins d’erreur déjà couverts par les
  tests cache, profils, options et émission.
- `BuildInputs`, `PackagePaths` : erreurs FS principalement exercées
  indirectement par les builds temporaires et les fixtures.
- `FreeVars`, `Metrics`, `Printer/Expr` : modules purs sans contrat d’erreur
  propre.

## Vérification

- Nouveau test exécuté seul : OK.
- Nouveau test seul : OK.
- Suite codegen complète : **102/102**.
- Aucun code du compilateur modifié dans cette étape.

Le document [`docs/ffi-boundaries.md`](../../../docs/ffi-boundaries.md) reste la
référence des entrées, sorties et erreurs par module.
