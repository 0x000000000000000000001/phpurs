# R7 — Filtrage des modules avant optimisation

Le 20 octobre 2026. Après lecture des CoreFn, `Main` sélectionne désormais
l’union des fermetures d’imports des racines `main` avant `buildModules`. Seuls
les modules sélectionnés passent par la capture FFI, le cache, l’optimisation,
la traduction, l’impression et l’écriture.

## Résultat sur `App`

| Mesure | Contrôle (tous) | Filtré | Réduction |
| --- | ---: | ---: | ---: |
| Modules optimisés | 306 | **108** | 198 |
| Fichiers PHP | 307 | **109** | — |
| Octets PHP | 5 789 010 | **1 766 903** | **69,48 %** |
| Backend total | 3 823,0 ms | **1 775,4 ms** | **53,56 %** |
| `optimize` | 1 286,7 ms | **363,9 ms** | 71,72 % |
| `translate` | 1 161,3 ms | **359,6 ms** | 69,03 % |
| `print` | 156,1 ms | **49,9 ms** | 68,01 % |
| `php.write` | 151,1 ms | **55,6 ms** | 63,23 % |

La lecture/décodage des 306 CoreFn reste nécessaire avant la sélection ; elle
n’est pas incluse dans le gain. Les 14 valeurs passent sur la sortie filtrée.

## Règles

- Un `--main` explicite doit être chargé et exporter `main`.
- Sans `--main`, l’union des fermetures de tous les mains exportés est retenue.
- Si aucun main n’est découvert, tous les modules chargés sont conservés.
- La fermeture suit les imports CoreFn, car chaque `index.php` charge ses imports.
- Les modules hors fermeture ne sont ni optimisés ni émis ; le manifeste retire
  leurs anciennes sorties après une finalisation réussie.
- Les bundles explicites utilisent la même fermeture ; les bundles automatiques
  utilisent l’union des racines.
- Les racines Composer/FFI restent celles de **tous les modules chargés** : le
  manifeste et le lock benchmark ne changent pas. `runp` a été rejoué avec
  succès, Composer inclus, et les 14 valeurs sont passées.

## Validation

- `missing-ffi` : module non chargé filtré, initialiseur atteignable toujours
  signalé, bundles automatique et explicite cohérents.
- `rewrite-limit` : le module multi-passes est rendu atteignable par un import.
- `file-emission` : les modules du main non sélectionné ne sont plus émis.
- `cache-cli` : une sortie retirée précédemment reste protégée jusqu’à une
  finalisation réussie.
- Build sans warning ; **100/100** contrôles codegen.

## Suite

Les 108 modules restants partagent encore le préambule runtime par namespace et
émettent tous leurs bindings. Les prochaines étapes R7 sont la DCE des bindings
depuis main puis un runtime partagé qualifié. Voir aussi le
[préambule sélectif](../runtime-preamble/report.md) et les
[bundles explicites](../bundle-reachability/report.md).

Artefacts :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-r7-filter-20261020`.
