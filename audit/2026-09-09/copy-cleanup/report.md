# Nettoyage des copies internes : intégration et mesures

La passe `Phpurs.CopyCleanup` est intégrée après l’inlining terminal et l’abaissement nullable. `altbak.pub-phpurs/bin/php/run -c` réussit. Le backend ne contient aucune règle nommant RBTree.

## Résultat mesuré

Comparaison après reconstruction complète, à TAST et environnement identiques, en désactivant uniquement CopyCleanup dans le témoin. Quatre processus frais séquentiels ABBA, PHP 8.5.4, OPcache CLI, JIT 1255, buffer 128 MiB, aucun Xdebug. Cache fichier OPcache désactivé et file_update_protection=0. Le chrono et le protocole de Bench.purs sont inchangés : échauffement global, puis trois warmups et minimum de dix mesures par test. La machine est partagée ; aucun autre travail lancé par cette tâche pendant les mesures.

| Ordre | Passe | RBTree (ms) | Somme des 14 minima (ms) | Pic PHP réservé (MiB) |
| --- | --- | ---: | ---: | ---: |
| 1 | désactivée | 142.962 | 162.371 | 26 |
| 2 | activée | 109.602 | 128.915 | 26 |
| 3 | activée | 113.499 | 133.057 | 26 |
| 4 | désactivée | 148.735 | 168.579 | 26 |

Gains appariés sur le total : **20.60 %** et **21.07 %**, soit **33.456–35.522 ms** économisées. RBTree baisse de 23.33–23.69 %. Les 14 résultats sont identiques ; le pic mémoire reste à 26 MiB.

La référence officielle du README d’altbak.pub reste **158,97 ms**, dont **140,005 ms** pour RBTree. La dernière colonne native donne historiquement **123,096 ms** pour RBTree. Les nouvelles mesures passent sous cette référence historique, mais le natif n’a pas été remesuré dans cette série. Le run isolé de `bin/php/run -c` donne 126,87 ms ; les pourcentages ci-dessus proviennent exclusivement de la comparaison ABBA.

Le prototype manuel donnait total 126–129 ms contre 152–153 ms (15,7–17,1 %). Ses résultats sont conservés séparément dans `prototype-*-results.json` et ne sont pas attribués à l’intégration.

## Transformation et preuve

CodeGen transmet les noms des fonctions et constructeurs des régions fermées déjà prouvées dans le TAST par EnumRegions. CopyCleanup ne traite que leurs PhpPrivateFunction à signature interne sans contrôles PHP. Les fonctions publiques restent intactes, ainsi que les workers issus uniquement de la fusion de thunks.

1. Réutiliser le threading borné des retours de TailInline pour rendre les positions terminales explicites.
2. Propager un alias local immédiatement avant un retour pur. Le retour ne contient que des valeurs, lectures de propriétés natives, opérateurs et constructeurs privés connus ; les appels et constructeurs externes restent exclus. Traiter les copies à rebours pour conserver les dépendances.
3. Retirer les initialisations de résultats devenues mortes.
4. Supprimer les allers-retours paramètre → temporaire → même paramètre en entrée uniquement si les destinations/sources sont distinctes, la séquence inverse est exacte et chaque temporaire possède exactement sa définition et sa lecture. Le critère ne dépend pas des noms générés. Les permutations, lectures ultérieures et vraies boucles gardent leurs copies.

La preuve TAST porte les types/layouts ; la preuve locale de flux porte les usages des variables. Le garde refuse tout le worker en présence de closures, callbacks dynamiques, affectations à travers un alias, références dans la signature ou PHP opaque. Seuls null, le fragment vide et le message d’échec de motif exact produit par CodeGen sont acceptés comme PhpRaw. Les appels curried imbriqués sont vérifiés jusqu’à leur cible globale, avant leur aplatissement par Printer.

Budgets par worker : 8 192 nœuds, profondeur 128, largeur 512, vérifiés par une pile explicite avant tout parcours récursif. Si un garde échoue, le worker original est conservé. Les classes, allocations, wrappers d’arité et frontières publiques restent inchangés.

## Vérification

- Huit suites AST : branch-slots, unary-callables, compact-loops, tail-inline, enum-regions, thunk-fusion, nullable-constructors et copy-cleanup.
- La nouvelle suite exécute le PHP avant/après : branches positives/négatives, copies chaînées/répétées, permutations, boucles paires/impaires et partage des objets. Elle vérifie aussi les refus pour captures par valeur/référence, mutation, callbacks, code opaque, constructeurs externes, signatures contrôlées, fonctions publiques et budgets larges/profonds.
- Seize fixtures compilées avec le fork TAST et exécutées en PHP et JS avec des sorties identiques. CopyCleanup ajoute une vraie boucle échangeant deux arbres sur 20 000/20 001 itérations ; les temporaires de cette boucle restent présents dans le PHP. Les quinze fixtures précédentes couvrent notamment les closures, FFI, frontières publiques, ombrages, récursion et jonctions de branches.
- Le runner ciblé réutilise les packages installés. Le runner global `bin/test` exige toujours phpurs-math, absent ; aucune validation globale non exécutée n’est revendiquée.
- Validation RBTree publique/privée après chacune de 1 285 insertions (croissant, décroissant, mélangé, clés négatives et doublons), invariants rouge-noir, profondeur et ordre. Les 1 290 paires d’anciennes racines sont revérifiées ; sérialisation, application partielle et TypeError publics sont préservés.
- Instrumentation séparée, sans JIT : **2 283 976 appels à ins et 2 583 932 nœuds T**, identiques avant/après. Aucune allocation de E ; aucun appel au worker balance sur la charge décroissante.
- Parmi 301 modules / 302 fichiers PHP, seul Test.RBTree change. Les 302 fichiers actifs correspondent à l’intégration. Le préfixe public de RBTree est identique. ins passe de **36 à 9 variables locales** ; le module de **123 361 à 113 141 octets**.

## Contrôles rencontrés pendant l’intégration

Le premier garde refusait les appels curried imbriqués et le diagnostic de pattern matching. Un traçage ciblé a identifié ces deux formes ; le garde a été élargi exactement à celles-ci, avec des régressions. Un stress de 8 192 feuilles a également reproduit un dépassement de pile dans le threading existant ; la limite de largeur est maintenant vérifiée avant ce parcours. Aucun nouvel avertissement dans CopyCleanup ; les 23 avertissements préexistants de Main/CodeGen restent hors de ce changement.

La reconstruction complète a actualisé un AppFFI ancien présent dans le cache TAST précédent. Les deux versions de comparaison ont donc été régénérées depuis le TAST frais, dans le cwd original et sans modifier modulePath. Elles correspondent désormais aux fichiers actifs ; la seule différence liée à la passe est RBTree. Le fichier `clean-rebuild-differences.json` garde cette observation.

## Reproduction et fichiers

`build.sh` sélectionne le compilateur standard pour l’outil JavaScript. `prepare-comparison.mjs` crée deux copies depuis le worktree actif, puis génère un backend témoin désactivant seulement CopyCleanup et un backend intégré depuis les mêmes modules compilés. Les sources et empreintes figurent dans sources.json/state.json. Les artefacts lourds sont dans raw/, ignoré par Git.

- `node tests/codegen/copy-cleanup.mjs` : tests directs après build de l’outil.
- `python3 audit/2026-09-09/copy-cleanup/run-fixtures.py` : fixtures isolées PHP/JS.
- `python3 audit/2026-09-09/copy-cleanup/validate.py` : structures et anciennes racines.
- `python3 audit/2026-09-09/copy-cleanup/count.py` : allocations/appels, distincts des chronos.
- `python3 audit/2026-09-09/copy-cleanup/measure.py full` : comparaison ABBA du protocole existant.

Le README partagé n’est pas modifié par cette intégration. Les résultats nouveaux sont dans ce bilan et le todo du backend, afin de conserver la référence officielle et d’éviter de recréer une divergence du tableau entre les worktrees.
