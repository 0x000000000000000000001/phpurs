# Tests codegen

Exécuter `npm run build` après une modification du compilateur, puis
`npm run test:codegen` (Node test runner, concurrence 1).

Pour cibler un fichier :

```sh
node --test tests/codegen/direct/enum-regions.mjs
node --test tests/codegen/cli/cache-cli.mjs
node --test tests/codegen/ffi/missing-ffi.mjs
```

Ces tests vérifient le générateur PHP et ses contrats. Les benchmarks de
performance restent dans altbak.pub ; les fixtures importées du langage ont
leur propre runner `bin/test`.

## Familles

| Répertoire | Responsabilité |
| --- | --- |
| [direct/](direct/) | Transformations du TAST et de l’AST PHP, représentations et code imprimé. |
| [cli/](cli/) | Options, cache, émission des fichiers, diagnostics et contrôle du runner. |
| [ffi/](ffi/) | Adaptation des valeurs étrangères, exports absents et runtime partagé. |

Les tests directs peuvent aussi couvrir une frontière FFI ou exécuter du PHP ;
la famille indique le point d’entrée principal, pas une exclusivité. Le script
npm découvre tous les fichiers `.mjs` de ces trois répertoires.

## Fixtures et conventions

- `fixtures/cli.mjs` : `temporaryRoot`, `runPhp`, `snapshotFiles`, `snapshotText`.
- `fixtures/cache-inputs.mjs` : construction de CoreFn typé minimal et fixtures partagées.
- `fixtures/runtime.mjs` : injection du runtime partagé pour exécuter un module
  imprimé hors d’un output complet.
- Les tests CLI utilisent `bin/phpurs.js` par défaut ; `PHPURS_TEST_CLI` permet
  de vérifier un autre exécutable construit avec la même interface.
- Les sorties PHP sont exécutées avec OPcache CLI désactivé dans les tests de
  correction.
- Chaque test doit nettoyer son répertoire temporaire via `temporaryRoot`,
  `t.after` ou `finally`.
- Les tests doivent utiliser leurs propres entrées et le code compilé local,
  sans dépendre d’une sortie d’application ou d’un chemin personnel.
- Un échec doit nommer la fixture et le contrat vérifié ; éviter les attentes
  implicites sur l’ordre des fichiers ou les timings.

Le formatage de ces scripts fait partie de `npm run format` et
`npm run format:check`. Les fixtures de `tests/passing` et les audits historiques
ne font pas partie de ce périmètre.

## Propriétés, couverture et smoke

- `direct/properties.mjs` : cinq suites `fast-check` (identité
  `children`/`mapChildren`, monotonie des variables libres, composition du
  renommage, déterminisme/sensibilité des clés de cache, imprimante sans
  marqueur d’erreur et parenthèses équilibrées).
- `npm run test:coverage` : `c8` sur `output/Phpurs.*`; rapport texte et JSON.
  La CI l’exécute sans blocage ; cible 70 % de statements sur les modules cœur.
- `npm run test:tast-smoke` : compile et exécute une fixture avec le fork TAST
  et les paquets `phpurs-*` voisins. Si l’un manque, le script affiche un skip
  explicite et se termine avec succès ; la CI le marque non bloquant.

## Contrats par passe

Chaque passe a un test direct qui couvre au minimum sa preuve, son repli et ses
budgets. Le golden fige la sortie imprimée ; `pass-protocol.mjs` couvre les
gardes et le parcours partagés ; `ast-traversal.mjs` couvre la traversée de
chaque constructeur `PhpExpr`.

| Passe | Contrats couverts | Test |
| --- | --- | --- |
| `PartialBindings` | grammaire des producteurs, callback scalaire, idempotence, budgets | `direct/partial-bindings.mjs` |
| `ThunkFusion` | graines littérales/capturées, portée lexicale, ordre d’évaluation, garde négative, budgets | `direct/thunk-fusion.mjs` |
| `StateFusion` | layout, helpers, projections, refus, comparaisons numériques, budgets | `direct/state-fusion.mjs` |
| `CallbackSpecialization` | forme du fold, callback natif, arité, contrats publics, budgets | `direct/callback-specialization.mjs` |
| `ArrayCallbacks` | contrats de source, folds/filtres, ordre, replis, budgets | `direct/array-callbacks.mjs` |
| `EnumRegions` | preuve de région, refus, layouts scalaires/nullable, budgets | `direct/enum-regions.mjs`, golden `adt` |
| `TailInline` | ordre d’évaluation, scopes, joins, arités, budgets | `direct/tail-inline.mjs` |
| `NullableConstructors` | sélection de layout, usages publics, profondeur/largeur | `direct/nullable-constructors.mjs` |
| `CopyCleanup` | `pureResult`, enfants supportés, largeur/profondeur, opacité | `direct/copy-cleanup.mjs` |
| `ArrayRefs` | sites d’appel, alias, mutations, TCO, point fixe | `direct/array-refs.mjs`, `direct/array-refs-ownership.mjs` |
| `CompactLoops` | convergence des captures, code brut, budgets | `direct/compact-loops.mjs` |
