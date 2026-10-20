# R7 — Runtime partagé qualifié

Le 20 octobre 2026. Intégré : les helpers recopiés par namespace sont déplacés
dans un `runtime.php` global unique, appelé avec des noms qualifiés `\phpurs_*`.

## Duplication actuelle

| Helper | Modules | Blocs | Appels |
| --- | ---: | ---: | ---: |
| `phpurs_curry_fallback` | 98 | 289 492 o | 940 |
| `phpurs_execute_effect` | 22 | 4 730 o | 157 |
| `phpurs_ref_*` | 3 | 891 o | 25 |
| **Total** | **99** | **295 113 o** | **1 122** |

Les classes `Phpurs_Data*` ne sont référencées par aucun module.

## Prototype

Le prototype déplace les trois helpers dans un `runtime.php` global de 3 270
octets, retire les blocs par namespace, ajoute un `require_once` aux 99 modules
concernés et qualifie les appels. Les constructeurs, initialisations et FFI ne
sont pas touchés.

| Mesure | Avant | Après | Gain |
| --- | ---: | ---: | ---: |
| Modules + entrée + runtime | 1 766 903 o | **1 493 656 o** | **15,46 %** |
| Bundle `App` autonome | 1 700 126 o | **1 409 562 o** | **17,10 %** |
| Démarrage modules, OPcache off | 24,79 ms | **20,66 ms** | 16,6 % |
| Démarrage modules, OPcache on | 48,23 ms | **40,10 ms** | 16,9 % |

Le bundle embarque le runtime une seule fois dans un bloc `namespace { ... }`
global ; les 99 modules qui utilisent un helper le chargent par un `require_once`
conditionnel vers `runtime.php` (3 419 octets). Le chargement direct de
`Data.Array/index.php` fonctionne, et les deux entrées `main.mod.php` /
`main.bundle.php` valident les 14 valeurs. `runtime.php` est possédé par le
manifeste modulaire et retiré proprement quand les modules sont désactivés.

## Validation finale

- **100/100** contrôles codegen, dont le helper d'exécution des tests unitaires et
  les attentes de manifeste/cleanup.
- `runp -c` : 14 benchmarks, **68,33 ms**.
- 110 fichiers PHP pour les modules (108 modules + entrée + runtime).
- Aucun flag : le runtime partagé est le comportement par défaut.
- `runtime.php` est traité comme une sortie modulaire fixe par le manifeste.

## Décision

Le gain est suffisant et ne modifie pas la surface publique des modules. Les
appels générés sont qualifiés ; les bundles restent autonomes. Le benchmark
externe `generated.py` appelle désormais `\phpurs_execute_effect` global.

Artefacts : `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-r7-shared-runtime-proto-20261020` et `.../phpurs-r7-shared-runtime-bundle-20261020`.
