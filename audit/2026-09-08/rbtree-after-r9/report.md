# RBTree après R9 : coût de la représentation des ADT

**Une copie diagnostique ramène RBTree à 156–160 ms et le total des 14 benchmarks à 223–227 ms**, contre respectivement 240–242 ms et 307–308 ms pour le témoin contemporain. Le gain total mesuré est de **26–27 %**. **Cette transformation n'est pas intégrée au compilateur** : les valeurs scalaires internes doivent être séparées de la représentation publique avant toute intégration.

## Expérience et résultat

Le snapshot contient le PHP réellement généré avec R0, R8 et R9. Les variantes ne changent que `Test.RBTree/index.php` dans des copies isolées. Elles conservent l'algorithme persistant, les 100 000 insertions décroissantes, les appels récursifs, les quatre rotations, les champs des nœuds `T`, les enveloppes d'appels et le calcul de profondeur. Aucun nœud d'arbre n'est muté ou supprimé.

On fait varier la représentation des constructeurs sans champ :

| Variante | Changement | RBTree isolé, ordre 1 | Ordre inversé |
| --- | --- | ---: | ---: |
| Témoin | Génération actuelle | 250,905 ms | 249,404 ms |
| Singletons | Réutiliser les objets `R`, `B`, `E` | 224,224 ms | 225,062 ms |
| Couleurs scalaires | `R = 0`, `B = 1`, comparaisons strictes | 190,341 ms | 189,487 ms |
| Constructeurs sans champ scalaires | Ajouter `E = null`, conserver `instanceof T` | 184,951 ms | 182,171 ms |
| Tests de nullité | Ajouter `T` testé par `!== null` dans le domaine fermé `Tree` | 166,843 ms | 166,654 ms |

La couleur scalaire procure déjà environ **60 ms**, contre environ 25 ms pour le seul partage des objets. L'arbre vide nullable et le remplacement des tests restants ajoutent environ 23 ms. Ces deltas localisent le potentiel ; ils ne constituent pas encore une règle de génération sûre pour des valeurs PHP arbitraires.

Comparaison complète, avec les vrais imports `App` pointant vers chaque variante :

| Ordre | Variante | RBTree | Total | Pic mémoire PHP réservé |
| --- | --- | ---: | ---: | ---: |
| 1 | Témoin | 241,665 ms | 308,288 ms | 42 MiB |
| 1 | Tests de nullité | 160,184 ms | 227,269 ms | 26 MiB |
| 2 | Tests de nullité | 156,465 ms | 223,356 ms | 26 MiB |
| 2 | Témoin | 239,810 ms | 306,942 ms | 42 MiB |

RBTree baisse de **34–35 %** dans ces contrôles complets. Les quatorze sorties attendues passent dans les quatre processus. Voir [bench-results.json](bench-results.json) et [full-results.json](full-results.json).

## Profil natif et comptes séparés

Le profil natif de cinq secondes, après échauffement, relève 4 265 échantillons sur le thread principal. Les handlers `instanceof` et `instanceof_function_slow` totalisent 488 feuilles, soit environ **11,4 %**. Création d'objets, écriture et destruction de leurs propriétés restent visibles. Le collecteur de cycles prend environ **138 ms sur 6,683 s**, soit **2,1 %**, sans cycle collecté. Une partie du JIT n'est pas symbolisée : ces chiffres ne forment pas une attribution exhaustive du temps. Voir [native-summary.json](native-summary.json) et [native-run.log](native-run.log).

Le premier échantillonnage a été refusé par la protection d'inspection des processus. Il a été relancé avec l'autorisation d'inspecter uniquement le processus PHP enfant lancé par le script ; ce second relevé réussit.

Des compteurs distincts, avec JIT désactivé, donnent par action :

| Construction/appel | Témoin | Singletons | Couleurs scalaires | Variante finale |
| --- | ---: | ---: | ---: | ---: |
| `R` | 199 979 | 1 | 0 | 0 |
| `B` | 299 957 | 1 | 0 | 0 |
| `E` | 200 002 | 1 | 200 002 | 0 |
| `T` | 2 583 932 | 2 583 932 | 2 583 932 | 2 583 932 |
| `ins` | 2 283 976 | 2 283 976 | 2 283 976 | 2 283 976 |
| `balance` | 0 | 0 | 0 | 0 |

Le gain provient donc de l'allocation et des tests des petites valeurs auxiliaires, sans suppression des constructions de nœuds persistants. Les temps instrumentés ne servent pas à évaluer les performances. Voir [allocation-counts.json](allocation-counts.json).

## Exactitude et frontière PHP

Les variantes produisent les mêmes empreintes canoniques après chacune de **1 285 insertions** : ordres croissant, décroissant, permutation déterministe de clés négatives et positives, puis doublons. Les **anciennes racines sont conservées** et leur empreinte est revérifiée après les insertions suivantes. L'arbre vide est également couvert. La normalisation explicite des couleurs et de l'arbre vide permet de comparer les valeurs entre représentations, sans masquer la différence de contrat PHP. Voir [validation-bench.json](validation-bench.json).

La sonde [boundary.php](boundary.php) constate effectivement les différences : le champ couleur n'est plus une instance de `B`, l'arbre vide n'est plus une instance de `E`, et la sérialisation JSON change. Le partage des singletons conserve ces classes mais change l'identité des couleurs entre deux racines. Les benchmarks corrects ne prouvent donc pas la compatibilité FFI de ces substitutions globales. Voir [boundary-observations.json](boundary-observations.json).

Le TAST fournit déjà tout le layout utile : `Color` contient les deux constructeurs sans champ `R` et `B`, et `Tree` contient `E` et `T Color Tree Int Tree`. Les champs se résolvent via les entrées 0, 1, 3, 1 de `typeTable`. Ces informations sont présentes dans le fichier local nommé `corefn.json`, qui contient bien le format enrichi du fork. Voir [tast-shapes.json](tast-shapes.json).

La difficulté suivante est de prouver où cette représentation peut rester interne. Une première intégration devra isoler un groupe de fonctions connues dont les valeurs d'ADT ne sont exposées ni aux FFI, ni à des appels inconnus, ni aux exports publics. Les chemins publics actuels restent nécessaires. Les types structurels et les instanciations TAST rendent les usages analysables ; ils ne suffisent pas à autoriser une modification globale du contrat PHP.

## Références et limites

Le [README actualisé d'altbak.pub](../../../../../altbak.pub-phpurs/README.md) indique **247,881 ms pour RBTree et 316,45 ms au total**, d'après le dernier relevé utilisateur. L'ancienne référence, conservée dans les bilans R0/R9, était de 269,606 ms et 381,61 ms. Les pourcentages annoncés ici utilisent les témoins contemporains, pas l'écart avec un relevé historique isolé.

La référence **PHP natif optimisé** du README est de **123,096 ms pour RBTree et 200,45 ms au total**. Notre copie se rapproche donc de cette référence, sans l'atteindre ni avoir remesuré le natif pendant cette expérience. Attention à la colonne native de style fonctionnel : son `RBTreeFFI.php` effectue des insertions croissantes puis des recherches, tandis que ce benchmark et `RBTreeFFICheatcode.php` font des insertions décroissantes puis une profondeur. Cette colonne ne permet pas d'attribuer un écart à la seule génération de code pour une charge identique.

PHP 8.5.4, OPcache CLI, JIT 1255 et buffer 128 MiB. Chaque mesure part dans un processus frais, sans cache de fichiers OPcache et sans Xdebug chargé. On conserve les trois échauffements, dix passages et minimum de `Bench.purs`, ainsi que l'échauffement global de `App` pour le total. Les variantes sont exécutées successivement dans les deux ordres. Le profil, les compteurs et les vérifications de structure sont séparés des mesures. La machine reste partagée.

## État et reproduction

Les **301 fichiers `index.php` actifs restent identiques par SHA-256**. L'empreinte RBTree demeure `3755e926d62e3055b04593d242df0c9a2f61534f2eadf524be7ef56cff97b20d`. Aucun changement de PHPurs, PBO, des sources des benchmarks ou du PHP actif ; seuls les scripts et résultats d'audit et le todo sont ajoutés/modifiés. Aucun rebuild ni commit effectué. Voir [inputs.json](inputs.json) et [final-state.json](final-state.json).

Depuis la racine PHPurs, avec la sortie active correspondant aux empreintes de `inputs.json` (`prepare.py` vérifie cet état et recrée les copies) :

```sh
python3 audit/2026-09-08/rbtree-after-r9/prepare.py
python3 audit/2026-09-08/rbtree-after-r9/native-sample.py
python3 audit/2026-09-08/rbtree-after-r9/measure.py
python3 audit/2026-09-08/rbtree-after-r9/measure.py full baseline null-checks
python3 audit/2026-09-08/rbtree-after-r9/count.py
```

La prochaine petite étape R10 doit tester le périmètre interne et ses contre-exemples avant d'intégrer la représentation scalaire. Le cas `E = null` vient ensuite, séparément des couleurs scalaires.
