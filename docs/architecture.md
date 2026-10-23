# Architecture du backend PHPurs

PHPurs transforme le CoreFn typé (TAST / `tcorefn`) du fork PureScript,
optimisé par PBO, en PHP, puis publie des
modules, des entrypoints et des bundles. Ce document donne la vue d’ensemble ;
les contrats détaillés restent dans `compiler.md`, `ffi-boundaries.md` et les
audits.

## Pipeline

```text
TAST / CoreFn typé (output/*/corefn.json)
  -> PBO, directives et contrats FFI
  -> BackendModule optimisé
  -> CodeGen (analyse TCO, bindings, expressions)
  -> PhpFile / PhpDecl / PhpExpr
  -> Printer (expressions, déclarations, runtime, module)
  -> module index.php / main.mod.php / main.bundle.php / bundle.php
  -> manifeste de sortie, cache et Composer
```

## Entrées et orchestration

- `Main` charge les CoreFn, choisit les modules mains et calcule la fermeture
  des imports (`Main.Selection`).
- `Main.Options` normalise les flags et budgets.
- `Main.Prepare` résout les racines Spago/FFI, capture les sources FFI, prépare
  les callbacks et le cache.
- PBO (`buildModules`) optimise les modules sélectionnés et fournit des
  `BackendModule`.

## Génération PHP

- `CodeGen` ordonne les passes et les preuves qui autorisent les spécialisations.
- `CodeGen.Expr` traduit récursivement les expressions ; `CodeGen.Declarations`
  produit les constructeurs, fonctions et valeurs du module.
- `CodeGen.Context` porte l’état lexical, les boucles et la position d’effet.
- `CodeGen.Tco` gère les labels, les sauts terminaux et les registres directs.
- `CodeGen.Effects` distingue une action différée d’une valeur déjà exécutée.
- `CodeGen.Bindings` analyse les groupes récursifs et l’ordre d’initialisation.
- Les passes locales (`ThunkFusion`, `StateFusion`, `CallbackSpecialization`,
  `ArrayCallbacks`, `EnumRegions`, `TailInline`, `CopyCleanup`, `ArrayRefs`)
  transforment l’AST PHP avant l’impression.

`Optimize.NeutralExpr`, `Optimize.Names` et `Optimize.ScalarWorker` partagent
les constructions communes. Chaque passe conserve ses propres preuves et
conditions de repli.

`ArrayRefs.Analysis` décide les promotions avec les sites d’appel, le TCO et
un point fixe. `Ownership` suit les alias et les lectures de champs d’un
propriétaire ; `Rebuild` applique la réécriture autorisée. `Support` fournit les
parcours et accesseurs partagés.

## Impression et runtime

- `Printer.Expr` rend les expressions, captures, curry et noms PHP.
- `Printer.Decl` rend les déclarations, corps exacts et le module complet.
- `Printer.Runtime` fournit les définitions globales uniques (curry, effect,
  références) ; les noms canoniques sont dans `RuntimeNames`.
- Les modules utilisent `runtime.php` ; les bundles embarquent le runtime une
  seule fois dans un bloc `namespace { ... }`.

## Sorties, cache et manifeste

- `ModuleState` publie les modules frais ou restaurés dans le même ordre.
- `BuildCache` relie les clés de contenu à l’état de module ; les entrées
  incomplètes ou ambiguës désactivent le cache.
- `OutputManifest` possède les fichiers PHP/Composer, conserve les modifications
  utilisateur et retire les sorties obsolètes après finalisation.
- `Main.Outputs` écrit les entrypoints et les bundles, puis finalise le
  manifeste.

## Entrypoints

- Mode modulaire : `main.mod.php` charge les modules atteignables.
- Bundle explicite : `main.bundle.php` contient la fermeture CoreFn et le
  runtime.
- Bundle automatique : union des fermetures des mains exportés.
- Sans main : tous les modules chargés sont conservés.

## Tests

Les tests sont classés dans `tests/codegen/README.md` :

- générateur direct/AST ;
- CLI/cache/émission (`tests/codegen/cli`) ;
- FFI/runtime (`tests/codegen/ffi`).

`npm run test:codegen` est l’entrée unique. Les helpers de fixtures et
d’exécution PHP sont dans `tests/codegen/fixtures/cli.mjs`.

## Invariants

- Le TAST optimisé n’est pas muté : les passes construisent de nouveaux nœuds.
- Les frontières FFI et les erreurs PHP sont préservées.
- Une extraction de module ne doit pas changer le PHP produit pour une entrée
  inchangée.
- Le cache et le manifeste doivent rester cohérents entre builds froids,
  partiels et restaurés.
