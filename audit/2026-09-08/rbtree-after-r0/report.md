# RBTree après R0 : profil et inlining diagnostique

Le coût massif de libération des variables locales observé avant R0 a disparu du profil natif. La meilleure nouvelle variante mesurée réduit RBTree d'environ **280 à 246 ms** et donne une somme des 14 benchmarks de **364–366 ms**. **Ce changement reste dans des copies diagnostiques ; le compilateur et le PHP actif n'ont pas été modifiés.**

## Protocole et référence

Charge inchangée : 100 000 insertions décroissantes, arbre persistant de Chris Okasaki, profondeur finale `22`. Le runner réutilise le protocole et le chrono de Bench.purs : trois échauffements, dix mesures, minimum. Les temps runtime excluent compilation et Composer.

PHP 8.5.4, OPcache CLI, JIT 1255, buffer 128 Mio ; Xdebug absent des mesures de performance. Les variantes ont un cache OPcache séparé et `file_update_protection=0` pour les copies nouvellement écrites. Elles sont exécutées séquentiellement, puis dans l'ordre inverse. La machine est partagée. Les journaux conservent le statut JIT, la mémoire et le GC.

Le [README historique d'altbak.pub](../../../../../altbak.pub-phpurs/README.md) donne **269,606 ms pour RBTree** et **381,610 ms au total**. La référence locale reste un peu plus lente. Les chiffres de la variante passent sous ces références, mais ils ne décrivent pas encore le backend livré.

## Profil actuel

L'échantillonnage natif de cinq secondes produit 4 258 échantillons du thread principal. Les handlers instanceof comptent 363 feuilles (8,5 %), auxquels s'ajoutent 45 feuilles d'instanceof_function_slow. Création d'objets, écritures et destruction des propriétés ressortent également : object_init_ex 137, zend_std_write_property 143, zend_object_dtor_property 191, zend_object_std_dtor 128, zend_objects_store_del 131. Ces valeurs ne sont pas une répartition exhaustive : une partie des frames JIT reste inconnue. `zend_free_compiled_variables`, dominant avant R0, ne figure plus parmi les feuilles publiées (seuil 5 échantillons).

Le collecteur de cycles consomme **146,9 ms sur 7 786,7 ms**, soit **1,89 %**, en 30 passages et sans cycle collecté. La destruction normale par références reste distincte du GC. Voir [native-summary.json](native-summary.json) et [native-run.log](native-run.log).

Xdebug sans JIT compte, par action :

| Fonction / construction | Nombre |
| --- | ---: |
| ins | 2 283 976 |
| balance | 2 183 976 |
| T | 2 583 932 |
| R | 199 979 |
| B | 299 957 |
| E | 200 002 |
| depth | 200 001 |

Les nombres R/B/E incluent les singletons initiaux du module. Sous instrumentation, ins représente 59,7 % du temps propre et balance 27,4 %. Ces proportions ne sont pas celles du JIT ; les deltas mémoire cumulés de Xdebug ne sont pas des comptes d'allocations. Voir [callgrind-summary.json](callgrind-summary.json).

## Expériences isolées

Les valeurs détaillées figurent dans [measurements.md](measurements.md).

- Résultats de branches stockés dans un seul temporaire de balance : 260,351–265,852 ms contre 280,469–286,281 ms. Gain modeste, environ 7 % sur RBTree.
- Construction suivie directement de la sortie de fonction : 262,554–265,139 ms.
- Quatre motifs de balance conservés dans un corps compact avec échecs partagés : 278,520–282,644 ms. Le code plus court ne suffit pas à accélérer ce test.
- Suppression diagnostique des contrôles d'arité : 275,997–276,223 ms. Pas de gros gain.
- Singletons R/B/E : 260,967–273,204 ms ; pic PHP réservé 30 → 16 Mio. Cette expérience concerne aussi la représentation/identité des objets et n'est pas intégrée.
- Suppression des copies TCO inutilisées dans ins/depth : 268,417–276,351 ms.
- GC désactivé : 299,173–300,474 ms, sans amélioration.

La variante retenue pour la mesure complète insère le corps compact de balance dans les deux appels terminaux de ins. Le contrôle compact-patterns précédent est conservé pour distinguer le raccourcissement des motifs et l'inlining. La variante conserve l'enveloppe publique de ins et laisse balance disponible pour les autres usages.

| Série isolée | Référence | Corps de balance intégré dans ins |
| --- | ---: | ---: |
| 1 | 281,561 ms | 246,323 ms |
| 2, ordre inversé | 279,213 ms | 245,793 ms |

L'amélioration observée est d'environ **12 % sur RBTree**. Elle combine un corps compact et l'inlining ; il faudra mesurer une implémentation générale, car une copie brute du grand decision tree pourrait augmenter les variables locales et perdre le gain.

## Effet sur la somme

App est copié dans chaque répertoire diagnostique pour que ses imports chargent effectivement le module RBTree de la variante. Les autres modules restent ceux de la référence. `full-bench.py` vérifie les 14 sorties à chaque run.

| Série complète | Variante | RBTree | Somme des 14 minima |
| --- | --- | ---: | ---: |
| 1 | référence | 288,246 ms | 407,279 ms |
| 1 | inlining | 245,297 ms | 363,877 ms |
| 2 | inlining | 248,428 ms | 366,258 ms |
| 2 | référence | 312,927 ms | 436,989 ms |
| Historique README | backend historique | ~269,606 ms | ~381,610 ms |

Le premier contrôle complet représente environ **43,4 ms / 10,7 % de temps en moins**. Le second contrôle est sensiblement plus lent ; il ne faut pas extrapoler son écart de 16 % comme un gain stable. Les temps de la variante restent proches sur les deux runs. Voir [full-results.json](full-results.json).

## Vérifications et état final

Les variantes produisent les mêmes empreintes structurelles après chaque insertion : listes vides, 257 clés croissantes puis décroissantes, permutation de −128 à 128 et cette permutation répétée pour les doublons. Cela couvre 1 285 insertions comparées par variante. [validation.json](validation.json) conserve les empreintes.

Une instrumentation distincte des mesures chrono confirme les mêmes nombres de constructions R/B/E/T et les mêmes 2 283 976 appels à ins dans la référence et la variante. Les **2 183 976 appels à balance disparaissent**, mais les **2 583 932 nœuds T sont toujours construits**. Voir [allocation-counts.json](allocation-counts.json). Il n'y a ni mutation de l'arbre ni modification des classes, tags ou champs.

Le PHP RBTree actif est identique par SHA-256 au début de l'expérience ; les sources du backend et les benchmarks n'ont pas changé : [final-state.json](final-state.json). Le TAST est présent dans le snapshot d'entrée. Aucun build n'était nécessaire pour cette étape sur des copies de PHP déjà généré ; les 14 benchmarks ont été exécutés quatre fois dans ces copies.

La prochaine petite étape R9 est une fixture d'appel terminal saturé puis un inlining générique borné, avec arguments évalués une seule fois dans l'ordre, renommage des variables/labels et maintien des fonctions publiques. L'éligibilité et le budget doivent être indépendants de Test.RBTree. Le TAST v3 fournit les types et instanciations ; il ne prouve pas l'absence d'alias requise pour supprimer ou muter les nœuds persistants.

## Reproduction

- `python3 native-sample.py` : échantillonnage natif du seul enfant PHP lancé par le script, après échauffement ; nécessite l'autorisation d'inspection de ce processus.
- `python3 ablate.py controls` : variantes de contrôle, deux ordres.
- `python3 ablate.py inline` : référence/inlining/temporaires, deux ordres ; prépare les copies nécessaires.
- `python3 full-bench.py` : quatre runs complets avec vérification des sorties.
- `python3 count-allocations.py` : comptes des appels et constructions, sans en tirer de temps de performance.

Les grands profils, snapshots TAST, caches et copies diagnostiques sont dans raw/, ignoré par Git.
