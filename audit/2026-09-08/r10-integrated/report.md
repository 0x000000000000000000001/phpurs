# R10 intégré : couleurs scalaires dans les régions fermées

La passe est intégrée au générateur PHPurs. `altbak.pub-phpurs/bin/php/run -c` passe et les 301 modules produits correspondent exactement à ceux du contrôle régénéré avec la version finale. La comparaison avec R10 désactivé retrouve le SHA256 RBTree antérieur à cette étape. Seul `Test.RBTree` change dans cette suite de benchmarks.

## Résultats

Deux processus par variante, dans l'ordre sans/avec puis avec/sans. PHP 8.5.4, JIT 1255, tampon de 128 MiB, aucun cache de fichiers OPcache, aucun Xdebug. Les warm-ups, les mesures dans le code PureScript et le meilleur de dix de `Bench.purs` sont conservés. Aucun compteur ni profilage dans les processus chronométrés. Les 14 sorties sont vérifiées à chaque run.

| Mesure | Sans R10 | Avec R10 |
| --- | ---: | ---: |
| RBTree | 249,012–252,657 ms | 176,234–177,767 ms |
| Total des 14 benchmarks | 316,650–320,236 ms | 241,648–242,707 ms |
| Pic mémoire PHP du processus complet | 42 MiB | 34 MiB |

Par paire : **23,35–24,54 % de temps total en moins**, **28,61–30,25 % sur RBTree**. Le run complet lancé par `bin/php/run -c` donne 233,82 ms ; les conclusions ci-dessus utilisent les comparaisons contrôlées répétées. Détails dans [rbtree-full-results.json](rbtree-full-results.json).

Le README officiel d'altbak.pub-phpurs conserve le relevé utilisateur de **316,45 ms**, dont **247,881 ms** pour RBTree. Les témoins actuels sont proches de ce relevé. Le natif optimisé historique est à **200,45 ms** au total et **123,096 ms** pour RBTree : la version finale reste environ 1,21 fois plus lente au total et 1,43–1,44 fois sur RBTree. Ce sont des repères historiques, pas des mesures natives contemporaines. Le RBTree natif de style fonctionnel effectue une autre charge ; son ratio n'est pas utilisé ici.

## Transformation et frontières

`Phpurs.EnumRegions` intervient sur `NeutralExpr`, après l'optimisation PBO et avant l'analyse TCO. L'analyse et la réécriture utilisent le même AST. Elle exploite les annotations `Typed`, les signatures monomorphes et les layouts `dataDecls` du TAST. Les wrappers `TypeApp` sont parcourus ; les instanciations polymorphes nécessitant une preuve supplémentaire restent exclues de cette première passe.

Une région doit rendre un scalaire et ses variables libres doivent avoir un type scalaire établi. Tous ses appels sont directs, locaux et saturés. Les corps des fonctions appelées sont vérifiés transitivement, y compris les cycles récursifs. Les appels externes/inconnus, les fonctions manipulées comme valeurs, les closures, les effets internes, les records/collections et les types ouverts ou inconnus font refuser la région. Les layouts admis sont locaux et monomorphes, avec des champs scalaires ou des ADT locaux eux-mêmes vérifiés.

Sur RBTree, la région reconnue est `depth (buildTree dummy E)` à l'intérieur de `act`. L'enveloppe Effect et l'appel externe `showIntImpl` restent autour. PBO ayant déjà simplifié certains auxiliaires, cinq fonctions sont copiées : `depth`, `balance`, `ins`, `insert`, `buildTree`. Les couleurs deviennent les entiers 0/1 ; `T` et `E` restent des objets dans deux classes internes distinctes. **L'arbre vide n'est pas remplacé par null.**

Les fonctions et les classes publiques gardent leur représentation, leur curry et leurs contrôles de types PHP. Les copies internes reçoivent des noms frais et aucune entrée dans `$GLOBALS`. Leurs types restent disponibles dans le TAST ; leurs paramètres et propriétés PHP évitent les contrôles dynamiques redondants sur les chemins prouvés. R9 continue d'inliner `balance`. Les noms PHP internes restent techniquement accessibles par réflexion ou par un appel PHP explicite ; ils ne sont pas des bindings PureScript exportés.

Budgets : 8 192 nœuds pour une preuve et pour l'ensemble des corps copiés, 32 fonctions, profondeur 128 et largeur 64. La recherche examine au plus 32 768 nœuds et 64 candidats. Les layouts et groupes de bindings ont aussi des bornes. Un parcours préalable à liste de travail évite qu'un AST refusé épuise la pile JavaScript ; toute région non prouvée conserve sa représentation initiale.

## Contrôles de propriétés : un coût mesuré

La première intégration ajoutait un champ couleur PHP `int` aux classes internes. Elle donnait 266,5–267,0 ms au total contre 309,9–310,3 ms dans ses témoins ([mesures initiales](rbtree-full-results-typed-fields.json)). Un contrôle isolé a comparé les mêmes fonctions et allocations, en changeant uniquement les déclarations des propriétés internes :

| Propriétés internes contrôlées par PHP | RBTree isolé, deux processus |
| --- | ---: |
| Couleur et clé `int` | 212,475–214,091 ms |
| Clé `int` seule | 196,301–197,159 ms |
| Types prouvés dans le TAST, propriétés PHP sans contrôle redondant | 175,932–176,980 ms |

La dernière variante est intégrée. Le jeu [rbtree-bench-results.json](rbtree-bench-results.json) garde les commandes historiques ; son nom `integrated` désigne la première version avec propriétés contrôlées. `prepare-field-controls.py` permet de reconstruire ces contrôles à partir du générateur final. Les différences entre les temps isolés et la suite complète ne sont pas utilisées comme un gain supplémentaire.

## Vérifications

- La suite directe `tests/codegen/enum-regions.mjs` vérifie sélection, refus, appels partiels/surappliqués, types inconnus, noms frais, budgets distincts de taille/largeur/profondeur, invariance des workers publics, constructeur vide référencé comme valeur et exécution PHP. Elle vérifie aussi qu'un mauvais argument de constructeur public provoque toujours une `TypeError`.
- Les quatre suites AST R0/R8/R9 passent : slots de branches, callables unaires, boucles compactes et inlining terminal.
- Treize fixtures passent en PHP et JavaScript. `InternalEnumRegion` exerce effectivement les copies internes, les FFI typées, les objets imbriqués, les appels publics partiels et les callbacks conservés. La fixture R8 vérifie aussi que seul le trajet attendu est compacté.
- Les arbres internes et publics donnent les mêmes valeurs canoniques sur 1 285 insertions croissantes, décroissantes, mélangées et avec doublons. Toutes les anciennes racines conservées restent identiques. Les objets publics gardent également leurs classes de couleur et leur sérialisation PHP.
- Les compteurs séparés donnent **2 583 932 constructions de T**, **200 001 constructions de E** et **2 283 976 appels à ins** dans les deux variantes. Les **199 978 R + 299 956 B** disparaissent du trajet interne. Aucun appel résiduel à son `balance`, aucun changement d'algorithme ni mutation des arbres.

Le runner officiel `bin/test` dépend de `phpurs-math`, absent de ce checkout. Les treize fixtures utilisent un projet isolé avec les packages voisins disponibles ; ce bilan ne prétend pas couvrir toute la suite officielle.

Le fichier RBTree passe de **69 913 à 126 203 octets** (+56 290) à cause des copies privées. Les empreintes et la comparaison des 301 modules sont dans [generated-code.json](generated-code.json). Les logs, sorties et scripts de contrôle accompagnent ce bilan ; les gros fichiers intermédiaires restent sous `raw/`, ignoré par Git.

## Reproduire

Depuis le checkout PHPurs, après `altbak.pub-phpurs/bin/php/run -c` :

```sh
node tests/codegen/enum-regions.mjs
python3 audit/2026-09-08/r10-integrated/run-fixtures.py
node audit/2026-09-08/r10-integrated/prepare-comparison.mjs
python3 audit/2026-09-08/r10-integrated/check-generated.py
python3 audit/2026-09-08/r10-integrated/check-rbtree.py
python3 audit/2026-09-08/r10-integrated/measure-rbtree.py full
```

La prochaine étape indépendante est la preuve et la mesure de `E = null` dans ces mêmes régions fermées.
