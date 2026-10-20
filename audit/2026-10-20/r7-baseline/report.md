# R7 — Baseline benchmark après intégration

Le 20 octobre 2026. Campagne PHP pure à trois processus validés sur la sortie
R7 courante (108 modules, `runtime.php` partagé, 110 fichiers PHP,
1 493 656 octets).

## Campagne validée

| Test | Médiane |
| --- | ---: |
| AST Evaluation | 2,407 µs |
| Fibonacci | 1,115 µs |
| List Processing | 68,860 µs |
| Tail Call Optimization | 77,066 µs |
| Deep Record Updates | 1 056,591 µs |
| Ackermann | 46,713 µs |
| Church Numerals | 2 143,578 µs |
| Prime Sieve | 790,279 µs |
| Red-Black Tree | 56 737,750 µs |
| Polymorphism | 6 654,438 µs |
| State Monad | 2,154 µs |
| Lazy Evaluation | 856,742 µs |
| Array Processing | 14,003 µs |
| RowToList | 0,962 µs |
| **Total** | **68,453 ms** |

Les trois processus donnent 69,546 / 67,346 / 68,472 ms et valident chacun les
14 sorties. La référence README historique est 65,78 ms ; elle n'est pas
contemporaine et n'est pas utilisée comme témoin de régression.

## Contrôle partagé vs runtime inline

Onze processus alternés comparent la sortie filtrée sans runtime partagé
(1 766 903 octets) à la sortie R7 avec `runtime.php` :

| Mesure | Contrôle | R7 partagé | Écart |
| --- | ---: | ---: | ---: |
| Total des 14 lignes | 67,813 ms | **67,911 ms** | **+0,14 %** |
| Church | 2 052,1 µs | 2 143,5 µs | +4,46 % |
| State | 1,32 µs | 2,21 µs | +0,9 µs absolus |
| RBTree | 56 260,0 µs | 56 323,0 µs | +0,11 % |
| Array | 14,34 µs | 13,82 µs | −3,63 % |

Le total est stable dans le bruit. Church conserve un écart d'environ +4,5 %
(+91 µs absolus) ; State et AST sont des cellules de quelques microsecondes.
Aucun autre test ne montre de régression large. Le runtime partagé est conservé.

## Suite

R8 est déjà intégré (voir le [bilan du 8 septembre](../../2026-09-08/r8-integrated/report.md)) :
il n'y a pas de nouvelle passe de captures à inventer ici. La prochaine piste
ouverte du backlog est **R3 — éliminer les enveloppes Effect avec une
information explicite**.

Artefacts : campagne et JSON de contrôle dans ce dossier.
