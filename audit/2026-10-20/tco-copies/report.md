# R4 — Supprimer les copies de paramètres TCO

Le 20 octobre 2026. Prototype sur la sortie R7 active, sans modification du
générateur. Les 46 boucles TCO passent leurs paramètres par un temporaire
`$__tco_var_*`, puis recopient le temporaire vers le paramètre au label de
boucle. Le prototype renomme les références vers les paramètres, supprime les
initialisations et les recopies, et conserve les affectations de saut déjà
calculées dans des temporaires.

## Inventaire

- 46 boucles TCO dans 20 modules.
- 85 copies d’initialisation et 85 recopies au label.
- 58 affectations de saut, toutes alimentées par `$__tco_*` ou un littéral.
- 174 lignes supprimées, 285 références renommées, `__tco_var_*` éliminé.
- PHP : 1 493 656 → **1 482 165 octets** (−0,77 %).

## Mesure ABBA, 11 processus

| Test | Contrôle | Prototype | Écart |
| --- | ---: | ---: | ---: |
| Tail Call Optimization | 75,81 µs | **51,10 µs** | **−32,60 %** |
| Lazy Evaluation | 815,60 µs | **518,65 µs** | **−36,41 %** |
| Polymorphism | 6 554,67 µs | **5 027,38 µs** | **−23,30 %** |
| Ackermann | 45,51 µs | **36,94 µs** | **−18,83 %** |
| Primes | 753,02 µs | **720,62 µs** | −4,30 % |
| List Processing | 67,60 µs | **64,69 µs** | −4,31 % |
| Records | 1 047,55 µs | **1 031,78 µs** | −1,51 % |
| RBTree | 56 146,71 µs | **55 629,96 µs** | −0,92 % |
| **Total** | **67,651 ms** | **65,208 ms** | **−3,61 %** |

State passe de 2,13 à 2,69 µs (+0,56 µs absolus), cellule négligeable.
Les 14 valeurs passent.

## Avant intégration

Le prototype renomme aussi les captures de closures imbriquées qui référencent
les temporaires extérieurs, notamment dans Primes. Il faut :

- porter la suppression dans `CodeGen` au niveau de la boucle TCO, pas par
  réécriture textuelle ;
- prouver la bijection temporaire/paramètre, l’usage unique des temporaires et
  l’absence de collision de noms ;
- conserver les captures par référence et les permutations d’arguments ;
- ajouter des fixtures pour dépendances croisées, closure capturant l’ancienne
  valeur, boucles imbriquées et arguments calculés ;
- replier sur le code actuel si une preuve manque ;
- rejouer R0/R8/R9/R10/R12, les tests TCO/ListOps/Records et `bin/php/run -c`.

Artefact :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-r4-tco-proto2-20261020`.
