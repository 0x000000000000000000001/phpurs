# Tests codegen

Point d’entrée unique : `npm run test:codegen` (Node test runner, concurrence 1,
`direct/`, `cli/` et `ffi/`).
Un fichier ciblé : `node --test tests/codegen/<fichier>.mjs`.

Ces tests vérifient le générateur PHP et ses contrats. Les benchmarks de
performance ne font pas partie de cette suite.

## Familles

| Famille | Fichiers |
| --- | --- |
| Générateur direct / AST | `direct/array-callbacks`, `direct/array-refs-ownership`, `direct/array-refs`, `direct/ast-metrics`, `direct/branch-slots`, `direct/callback-specialization`, `direct/compact-loops`, `direct/copy-cleanup`, `direct/effect-results`, `direct/enum-regions`, `direct/exact-arity`, `direct/free-vars`, `direct/nullable-constructors`, `direct/nullary-constructors`, `direct/operand-accumulator`, `direct/partial-bindings`, `direct/record-update-fusion`, `direct/recursive-initialization`, `direct/references`, `direct/state-fusion`, `direct/tail-inline`, `direct/tco-direct`, `direct/thunk-fusion`, `direct/unary-callables` |
| CLI / cache / émission | `cli/build-profile`, `cli/cache-cli`, `cli/cache-key`, `cli/composer-merge`, `cli/file-emission`, `cli/module-cache`, `cli/package-paths`, `cli/purmeta-budget`, `cli/purmeta-profile`, `cli/rewrite-limit` |
| FFI / runtime | `ffi/missing-ffi`, `ffi/runtime-preamble` |

Les tests directs peuvent aussi couvrir une frontière FFI ou exécuter du PHP ;
la famille indique le point d’entrée principal, pas une exclusivité.

## Fixtures et conventions

- `fixtures/cli.mjs` : `temporaryRoot`, `runPhp`, `snapshotFiles`, `snapshotText`.
- `fixtures/cache-inputs.mjs` : construction de CoreFn minimal et fixtures partagées.
- `fixtures/runtime.mjs` : injection du runtime partagé pour exécuter un module
  imprimé hors d’un output complet.
- Les sorties PHP sont exécutées avec OPcache CLI désactivé dans les tests de
  correction.
- Chaque test doit nettoyer son répertoire temporaire via `temporaryRoot` ou
  `t.test.after`.
- Un échec doit nommer la fixture et le contrat vérifié ; éviter les attentes
  implicites sur l’ordre des fichiers ou les timings.
