# R10 — Couleurs internes et ADT publics conservés

**La fixture et le prototype passent.** Une copie de RBTree conservant les fonctions et ADT publics donne **253–261 ms au total**, contre **304–310 ms** pour le témoin contemporain. **Ce résultat n'est pas intégré dans PHPurs.** Les adaptateurs textuels de cet audit devront être remplacés par une transformation des AST du générateur.

## Périmètre vérifié

`InternalEnumRegion` déclare `Color = Red | Blue` et une chaîne persistante contenant ces couleurs. L'entrée `score :: Int -> Int` ferme leurs usages dans `score`, `build`, `flipColor` et `weight`.

L'analyse lit le **TAST enrichi réel** : `dataDecls`, `typeTable`, annotations et applications de types explicites. Elle suit les appels directs saturés et les layouts locaux monomorphes, avec entrée/sortie scalaires. Types ou formes inconnus, fonctions utilisées comme valeurs, callbacks locaux, applications partielles internes et sorties d'ADT vers des appels externes sont refusés. Budgets : 64 fonctions, 8 192 visites d'expressions, 64 tours de fermeture des layouts. Voir [regions.py](regions.py) et [regions.json](regions.json).

La fixture passe avec le backend actuel **en PHP et JavaScript**. Elle couvre les entrées opaques, applications partielles publiques, anciennes racines, couleur passée à une FFI PHP typée par ses classes de constructeurs, arbre/record contenant ces couleurs, appel inconnu, fonction exportée et callback conservé puis invoqué deux fois.

L'analyse refuse les sept entrées non éligibles. Sept autres contrôles portent sur les annotations absentes/inconnues, appels indirects, application partielle, fuite dans un helper transitif et budgets. Renommer module et layout reste accepté : aucun nom de benchmark ne participe au critère. Voir [analysis-checks.json](analysis-checks.json).

Le record de `observedBox` porte explicitement les champs `Color` et `Chain`, mais sa queue de rangée référence `Any` dans cette émission. Le prototype refuse conservativement ce type au lieu d'en supposer la fermeture ; les layouts et champs restent disponibles.

## Prototype et frontières PHP

Des copies non exportées des fonctions et une classe de nœud sont ajoutées dans un namespace interne. Seules ces copies utilisent les couleurs entières. L'entrée scalaire conserve son enveloppe ; les autres fonctions, classes et bindings publics restent identiques. Les valeurs internes sont consommées avant le retour scalaire, sans conversion récursive d'arbre aux appels publics.

La fixture modifiée repasse toutes ses assertions. `score 8` construit **8 nœuds internes et aucun objet couleur**, contre 8 nœuds publics et 9 objets couleur dans le témoin. Les appels publics à `build`, y compris partiels, conservent **la même sérialisation PHP**, leurs constructions, scores et anciennes racines. Voir [prototype.json](prototype.json) et [runtime-probe.json](runtime-probe.json).

Cela valide les frontières testées, pas tous les usages FFI possibles. Les fonctions internes ne figurent pas dans les bindings PureScript ; PHP peut toujours nommer une fonction de namespace explicitement.

## Application au vrai RBTree

Le critère limité aux déclarations scalaires ne sélectionne aucune entrée utile : `act` retourne `Effect String`. L'analyse cherche donc aussi des **sous-expressions scalaires avec variables libres scalaires**, sans closure ni liaison lexicale à l'intérieur du candidat.

Elle identifie `show (depth (buildTree dummy E))` : **`dummy :: Int` est la seule variable libre, `String` le résultat**. Les fonctions atteintes sont `balance`, `buildTree`, `depth`, `ins`, `insert`, `makeBlack`, `max`. L'effet opaque et son moment d'exécution restent inchangés. Voir [rbtree-regions.json](rbtree-regions.json).

L'adaptateur est limité à l'empreinte du PHP audité. Il ajoute les fonctions et la classe `T` internes, puis redirige les deux appels de cette expression. **Tout le code public précédant `act` reste identique octet par octet.** `E` conserve sa représentation objet ; seules les couleurs changent. Voir [rbtree-prototype.json](rbtree-prototype.json).

## Mesures

| Ordre | Variante | RBTree | Total des 14 tests | Pic PHP réservé |
| --- | --- | ---: | ---: | ---: |
| 1 | Témoin | 237,456 ms | 303,634 ms | 42 MiB |
| 1 | Couleurs internes | 189,465 ms | 253,288 ms | 34 MiB |
| 2 | Couleurs internes | 196,903 ms | 260,868 ms | 34 MiB |
| 2 | Témoin | 241,711 ms | 309,659 ms | 42 MiB |

Soit **15,8–16,6 % de temps total en moins**, et **18,5–20,2 % sur RBTree**. Les 14 sorties passent dans les quatre processus. Les copies ajoutent 57 813 octets au module : 69 913 → 127 726 octets. Leur sélection et leur taille devront être bornées à l'intégration.

Les arbres internes et les chemins publics sont vérifiés séparément après **1 285 insertions** : ordres croissant, décroissant, mélangé et doublons. Les anciennes racines restent intactes. Voir [rbtree-internal-checks.json](rbtree-internal-checks.json) et [rbtree-validation-full.json](rbtree-validation-full.json).

Compteurs distincts, sans JIT, après initialisation du module :

| Par action | Témoin | Couleurs internes |
| --- | ---: | ---: |
| Objets `R` | 199 978 | 0 |
| Objets `B` | 299 956 | 0 |
| Objets `E` | 200 001 | 200 001 |
| Nœuds `T` | 2 583 932 publics | 2 583 932 internes |
| Appels `ins` | 2 283 976 publics | 2 283 976 internes |

Les trois singletons publics initiaux sont exclus, contrairement au comptage précédent. Aucun nœud `T` ni appel récursif n'est supprimé. Voir [rbtree-counts.json](rbtree-counts.json).

Protocole inchangé de `Bench.purs` : échauffement global, trois échauffements par test et meilleur de dix. PHP 8.5.4, OPcache CLI, JIT 1255/128 MiB, processus frais sans cache de fichiers OPcache ni Xdebug. Les deux ordres s'exécutent successivement ; compteurs et contrôles structurels sont séparés des chronos. Voir [rbtree-full-results.json](rbtree-full-results.json).

Le [README actuel](../../../../../altbak.pub-phpurs/README.md) donne **316,45 ms au total et 247,881 ms pour RBTree**. La référence PHP natif optimisé reste **200,45 ms et 123,096 ms**, non remesurée ici. Les précédents 223–227 ms incluaient aussi l'arbre vide nullable et une substitution globale ; ce n'est pas la mesure de cette étape.

## État et suite

Les **301 modules PHP actifs restent identiques**. Aucun changement du compilateur PHPurs ou de PBO ; une fixture, cet audit et le suivi du todo sont ajoutés/modifiés. Aucun commit ni rebuild du backend. La suite habituelle dépend encore de `phpurs-math`, absent ; la fixture utilise les paquets frères dans un projet isolé. Voir [source-state.json](source-state.json).

Prochaine étape : porter l'analyse bornée et la duplication interne dans les AST typés/PHP de PHPurs, avec des noms frais. Relier la région typée au bon site après optimisation, sans rechercher du PHP imprimé. Rejouer ces contre-exemples et les régressions R0/R8/R9, puis régénérer et mesurer à passe activée/désactivée. L'arbre vide nullable reste une transformation ultérieure distincte.

Reproduction depuis la racine PHPurs :

```sh
python3 audit/2026-09-08/r10-boundary/run-fixtures.py
python3 audit/2026-09-08/r10-boundary/check-regions.py
python3 audit/2026-09-08/r10-boundary/prototype.py
python3 audit/2026-09-08/r10-boundary/probe.py
python3 audit/2026-09-08/r10-boundary/expression-regions.py ../../altbak.pub-phpurs/run/bak/php/output/Test.RBTree/corefn.json > audit/2026-09-08/r10-boundary/rbtree-regions.json
python3 audit/2026-09-08/r10-boundary/rbtree-prototype.py
python3 audit/2026-09-08/r10-boundary/measure-rbtree.py full
python3 audit/2026-09-08/r10-boundary/check-rbtree.py
```
