# R7 — Préambule runtime sélectif

Le 20 octobre 2026. Le préambule était copié intégralement dans chacun des
**306 modules** : il pesait **8 159 octets**, dont **4 693 octets** de classes
génériques `Phpurs_Data0`–`Phpurs_Data12`. Aucun module du corpus n'utilise ces
classes. Le préambule n'émet désormais que les morceaux réellement référencés.

## Inventaire

| Élément | Modules concernés |
| --- | ---: |
| Classes `Phpurs_Data*` | 0 / 306 |
| `phpurs_curry_fallback` | 284 / 306 |
| `phpurs_execute_effect` | 59 / 306 |
| Helpers `phpurs_ref_*` | 3 / 306 |

Les occurrences sont cherchées dans le corps assemblé (FFI, déclarations brutes
et déclarations générées), avant l'ajout du préambule. Une mention de
`Phpurs_Data` émet le bloc complet ; une mention d'un helper émet son groupe.

## Résultat

| Mesure | Avant | Après |
| --- | ---: | ---: |
| Fichiers PHP | 307 | 307 |
| Modules modifiés | — | 306 |
| Octets PHP | 7 431 336 | **5 787 194** |
| Réduction | — | **−22,12 %** |

Après l'étape bundle, l'entrée explicite liste 108 modules et le total actif est
**5 789 010 octets** (−22,10 %).

Le chargement des **108 modules** de la fermeture réelle a été mesuré cinq fois
par variante, sans exécuter les tests (198 modules restent hors fermeture) :

| OPcache CLI | Avant | Après | Réduction |
| --- | ---: | ---: | ---: |
| désactivé | 31,225 ms | **24,532 ms** | 21,4 % |
| activé | 63,153 ms | **48,732 ms** | 22,8 % |

Les valeurs absolues avec OPcache varient selon la charge de la machine ; une
campagne antérieure donnait 31,9 → 24,7 ms, avec la même réduction relative.
Le gain est un gain de **démarrage et de taille**, pas un gain des boucles
chaudes. Aucun résultat des 14 benchmarks ne change.

## Validation

- Build sans warning.
- **99/99** contrôles codegen, dont le nouveau `runtime-preamble.mjs`.
- Les 14 valeurs passent après régénération.
- Le test vérifie l'absence des classes/helpers inutilisés et leur présence
  lorsqu'une référence existe, y compris les trois helpers de référence et le
  bloc complet de classes génériques.
- Quatre fixtures ciblées passent : `ExactArity`, `RecordUpdateFusion`,
  `FunctionFFIBoundary` et `EffFn`.

## Limites et suite

Cette étape ne filtre pas encore les modules hors fermeture d'imports, ne
déduplique pas les helpers entre namespaces et n'enlève aucun binding inutilisé.
`App/main.mod.php` charge 108 des 306 modules ; le bundle automatique en contient
toujours 306, ce qui motive la prochaine comparaison.
Ce sont les étapes suivantes de R7. Les scripts de mesure sont
[`startup.php`](startup.php), [`measure_startup.py`](measure_startup.py) et les
résultats dans [`results.json`](results.json).

Prochaine micro-étape recommandée : comparer les modules du bundle à ceux du
mode modulaire pour un `--main` explicite, puis mesurer la fermeture d'imports
avant toute DCE de bindings.
