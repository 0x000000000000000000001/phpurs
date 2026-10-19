# R1 — Extension FFI mesurée puis refusée

Le 19 octobre 2026. Le corps exact a été étendu aux wrappers FFI scalaires de
`GenNativeForeign.purs`, puis comparé sur le même TAST de benchmarks. **Aucun
gain reproductible n'a été obtenu** ; la première architecture ralentissait
même clairement les parcours. Le code de l'extension a été retiré. R1 reste
limité aux fonctions natives de `Printer.purs`, déjà validées.

## Pourquoi l'extension semblait utile

`Data.Semiring.intAdd` et les autres FFI scalaires sont appelés de nombreuses
fois par les workers spécialisés. Leur wrapper public exécute `func_num_args`,
construit `$__fn`, teste l'application partielle et cherche l'export dans
`$ffi_...`. Un corps exact supprime cette prologue et garde la recherche
d'export, les contrôles d'arguments et le contrôle de retour.

## Expérience A — wrapper déléguant au corps exact

Le wrapper public appelait `name__phpurs_exact`. Les appels inter-modules
continuaient à utiliser le wrapper, qui ajoutait alors un appel de fonction
supplémentaire. Résultat isolé, quatre processus miroir :

| Charge | Contrôle | Exact |
| --- | ---: | ---: |
| ListOps | 67,29 µs | 68,95 µs |
| ArrayOps | 13,53 µs | 15,64 µs |

La suite complète passait de 68,76 à 70,75 ms. La régression d'ArrayOps
(**+15,6 %**) est nette : le wrapper délégué coûte plus que la prologue qu'il
évite.

## Expérience B — wrapper original et table exacte globale

Pour corriger A, le wrapper a conservé son corps d'origine et une table
`globalKey -> nom exact` a été accumulée entre modules. Les appels directs
pouvaient alors cibler les corps exacts sans ajouter d'appel au chemin public.
La table était restaurée avec les états de cache et les wrappers d'origine
restaient inchangés.

| Mesure | Contrôle | Exact |
| --- | ---: | ---: |
| Total (médiane ABBA) | 68,996 ms | 70,097 ms |
| ListOps isolé | 67,45 µs | 67,21 µs |
| ArrayOps isolé | 13,52 µs | 13,62 µs |
| StateMonad isolé | 2,717 µs | 2,749 µs |
| RBTree isolé | 55 995 µs | 56 500 µs |
| Fichiers PHP modifiés | — | 74 |
| Corps exacts | 0 | 144 |
| Octets PHP | 7 430 597 | 7 473 186 |

Le gain de ListOps (environ 0,24 µs) est dans le bruit ; ArrayOps et StateMonad
sont légèrement plus lents, et le total augmente. L'extension ajoute
**42 589 octets** et 144 fonctions sans avantage reproductible. Le JIT PHP
semble déjà absorber l'essentiel de la prologue des wrappers FFI.

## Décision

L'extension FFI n'est **pas intégrée**. Le code a été restauré à l'état R1
natif : 47 corps exacts dans 22 modules, wrapper public délégant conservé,
Fib 1,527 → 1,120 µs et Ackermann 48,523 → 44,942 µs. La case « étendre aux
wrappers FFI » reste ouverte avec cette mesure négative ; une nouvelle tentative
devrait d'abord démontrer un wrapper FFI dont la prologue est réellement
mesurable, ou proposer une représentation qui évite la duplication du lookup.

Les résultats des deux expériences et les mesures isolées sont dans
[`results.json`](results.json). Les sorties et correctifs de l'expérience sont
conservés sous
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-r1-ffi-audit-20261019`
et `.../phpurs-r1-ffi-audit2-20261019`.

## Reproduction

Le script [`measure.py`](measure.py) régénère deux sorties à partir des mêmes
CoreFn, compare les fichiers, puis exécute l'ordre ABBA et valide les 14
valeurs. Il faut lui fournir les exécutables de contrôle et d'expérience ; les
artefacts de cette campagne contiennent `ffi-experiment.patch` pour retrouver
la variante testée.
