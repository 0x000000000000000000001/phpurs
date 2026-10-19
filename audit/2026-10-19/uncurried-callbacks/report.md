# R2 — Inventaire des formes explicitement uncurried

Le 19 octobre 2026. **Aucune des charges nommées par R2 ne contient de
`UncurriedAbs` ou `UncurriedEffectAbs`.** L'étape n'a donc pas été implémentée :
la prémisse « Church, ArrayOps et StateMonad contiennent des closures
explicitement uncurried » n'est pas vérifiée sur le TAST optimisé actuel.

## Méthode

[`capture.mjs`](capture.mjs) capture les modules `Test.Church`, `Test.ArrayOps`
et `Test.StateMonad` après PBO. [`count.mjs`](count.mjs) parcourt tous les
modules du programme pur et compte les quatre formes `Uncurried*`. Les nœuds
sont tagués par constructeur avant le comptage, comme dans les captures
d'audit précédentes.

## Résultats

| Module | UncurriedAbs | UncurriedEffectAbs | UncurriedApp | UncurriedEffectApp |
| --- | ---: | ---: | ---: | ---: |
| `Test.Church` | 0 | 0 | 0 | 0 |
| `Test.ArrayOps` | 0 | 0 | 4 | 0 |
| `Test.StateMonad` | 0 | 0 | 0 | 0 |
| `Main` du fixture `EffFn` | 0 | 0 | 0 | 1 |

Les quatre `UncurriedApp` d'ArrayOps sont les appels FFI `filterImpl` et
`foldlArray`, déjà couverts par les contrats `ForeignTraversals` et
`ArrayCallbacks` ; ce ne sont pas des closures locales.

Les formes restantes vivent dans les bibliothèques :

| Module | UncurriedAbs | UncurriedEffectAbs | UncurriedApp | UncurriedEffectApp |
| --- | ---: | ---: | ---: | ---: |
| `Data.Map.Internal` | 19 | 0 | 92 | 0 |
| `Data.Set` | 3 | 0 | 9 | 0 |
| `Data.Set.NonEmpty` | 2 | 0 | 0 | 0 |
| `Effect.Uncurried` | 0 | 20 | 0 | 20 |
| `Effect.Aff.Compat` | 0 | 4 | 2 | 2 |

## Conclusion

R2 ne peut pas respecter son critère sur Church/ArrayOps/StateMonad : ces
modules n'ont aucun corps explicitement uncurried à préserver. Les cibles
réelles sont `Data.Map`/`Data.Set` et `Effect.Uncurried`, qui ne dominent pas
les 14 benchmarks. La case R2 reste ouverte mais **dépriorisée** ; il faudrait
d'abord une charge qui exerce réellement ces closures, ou une mesure isolée de
`Data.Map.Internal`/`Effect.Uncurried`. Aucun changement du générateur n'a été
effectué pour cette étape.

Les captures et comptages sont conservés sous
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-r2-capture-20261019`
et `.../phpurs-r2-capture-20261019/uncurried-counts3.log`.
