# R3 — Inventaire des enveloppes Effect

Le 20 octobre 2026. Inventaire de la sortie R7 active : 108 modules, 110
fichiers PHP, `runtime.php` partagé.

## Appels au helper

`\phpurs_execute_effect(...)` apparaît **157 fois** dans **22 modules** :

| Forme de l’argument | Appels |
| --- | ---: |
| variable | 43 |
| expression parenthésée / dispatch dynamique | 65 |
| appel de fonction direct | 46 |
| global `$GLOBALS[...]` | 3 |
| closure littérale | 0 |

Répartition : Bench 60, App 34, Data.Array 21,
Control.Monad.Rec.Class 9, ST Internal 6, ST Iterator 6, ST 4,
Effect.Console 3, puis **un appel dans chacun des 14 modules `Test.*`**.

Les variables passées au helper viennent surtout du harness (`Bench_opaque`,
`benchNow`, logs) ou de valeurs pures (`null`) ; les appels directs sont
`Effect.Console.log`, `Bench.run/measureBatch/calibrate` et les opérations ST.
Ils sont majoritairement exécutés **une fois par action ou par lancement de
benchmark**, pas dans les boucles de 900 éléments, 100 000 insertions ou
10 millions de lookups.

## Closures

- 2 463 closures générées, dont 1 476 avec captures (2 743 entrées).
- Aucune clause `use ()` vide.
- 277 occurrences de fermeture immédiate syntaxique ; elles servent surtout à
  délimiter une action ou une portée, pas à répéter un travail chaud.

## Conclusion

R3 n’a pas de gain démontrable sur les 14 benchmarks actuels : même en
supprimant entièrement le helper, 157 appels à quelques dixièmes de microseconde
restent négligeables devant 68,45 ms, et les sites chauds ne sont pas concernés.
StateMonad est une monade pure et ne valide pas ce chantier.

Deux options sont possibles :

1. **Ne pas intégrer R3 maintenant** et passer à R4, qui touche directement les
   boucles TCO.
2. Créer d’abord un benchmark Effect/bind/Ref dédié, long et validé, pour
   mesurer une vraie chaine d’actions avant toute transformation.

Recommandation : R4. Si R3 est repris plus tard, commencer par le benchmark
dédié, puis seulement la distinction pure/action/résultat dans le TAST.
