# R9 — Inlining terminal intégré

Le backend régénère désormais un PHP qui supprime les deux appels terminaux de `ins` à `balance`, sans traitement spécifique à RBTree. Les 14 résultats restent exacts. Le total passe de **399,670–409,605 ms à 355,170–358,711 ms** dans une comparaison contrôlée, soit **11,1–12,4 % de temps en moins**. RBTree passe de **285,328–292,116 ms à 239,411–240,267 ms** (16,1–17,8 % de moins).

Les changements sont dans le checkout PHPurs existant ; aucun commit ni merge n'a été effectué pour cette étape. Les checkouts principaux de PBO et altbak.pub n'ont pas été modifiés.

## Transformation

`CodeGen` expose maintenant les affectations, sauts et labels des branches avec les nœuds structurés de `PhpAst`. La nouvelle passe `Phpurs.TailInline` s'exécute avant l'impression PHP :

1. Suivre les continuations qui retournent un local ; remplacer les jonctions correspondantes par des retours. Conserver une affectation observable par une closure, y compris capturée par référence.
2. Sur un corps feuille éligible, substituer les copies simples utilisées exactement une fois dans le retour qui les suit. Les dépendances entre copies sont traitées en remontant le bloc.
3. Insérer ce corps dans un appel terminal saturé à une fonction native du même module. Les arguments sont affectés une seule fois, de gauche à droite, à des locaux renommés. Un préfixe frais évite aussi les noms présents dans les captures du caller. Les deux sites terminaux d'un même callee peuvent partager ces locaux, leurs exécutions ne se chevauchant pas dans une activation.
4. Garder la fonction publique et son protocole de curry/surapplication. La passe ne descend pas dans les closures et n'effectue aucune expansion itérative.

Les cibles contenant un appel, une closure, une boucle, une mutation d'objet/tableau, du PHP brut non reconnu ou des sauts résiduels sont exclues. Tous les chemins doivent terminer, tous les locaux lus doivent appartenir aux paramètres/affectations du corps. L'absence d'appels exclut également les fonctions récursives. Les contrôles de types PHP réellement émis sur le premier paramètre ou le retour interdisent l'inlining ; les annotations TAST des paramètres suivants ne sont actuellement pas imprimées par `genNativeCurry`. Un test couvre cette distinction.

Le plafond est de **1 536 nœuds d'expression par corps**, et **3 072 nœuds ajoutés par caller**, arguments inclus. Ce ne sont pas des nombres d'instructions PHP. Le premier plafond expérimental de 1 024 excluait le corps compact de `balance` (1 045 nœuds) ; la limite a été ajustée explicitement après observation. Il n'y a aucun nom de benchmark dans le critère de sélection.

Le TAST v3 fournit toujours les types, instanciations et layouts utilisés en amont. Cette passe traite le flot de contrôle du PHP AST ; elle n'ajoute pas de nouvelle exploitation de `TypeApp`, ni de représentation FFI, ni d'hypothèse d'unicité des arbres persistants.

## Mesure du code réellement généré

`prepare-comparison.mjs` copie le PHP actif dans un projet isolé et reconstruit un témoin à partir des mêmes tcorefn, du même backend et des mêmes directives PBO, en remplaçant seulement `TailInline.optimize` par l'identité dans un bundle temporaire. Les chemins des sources dans les métadonnées CoreFn sont rendus absolus pour retrouver exactement les mêmes FFI depuis le dossier isolé. Aucun fichier actif n'est remplacé.

La comparaison des 299 modules générés montre que **seul Test.RBTree diffère** entre les deux variantes. Les modifications de ce module proviennent de la passe générique, sans réécriture manuelle du PHP.

Les chronos sont ceux de `Bench.purs` : trois échauffements puis minimum de dix passages, et somme des quatorze minima. PHP 8.5.4, OPcache CLI, JIT 1255, buffer 128 MiB ; l'état JIT actif est vérifié pour chaque processus. Aucun cache de fichiers OPcache n'est réutilisé dans la comparaison, aucun profiler ni compteur n'est actif pendant les mesures. Les processus s'exécutent successivement, dans les deux ordres.

| Ordre | Variante | RBTree | Total |
| --- | --- | ---: | ---: |
| 1 | Passe désactivée | 285,328 ms | 399,670 ms |
| 1 | Passe intégrée | 239,411 ms | 355,170 ms |
| 2 | Passe intégrée | 240,267 ms | 358,711 ms |
| 2 | Passe désactivée | 292,116 ms | 409,605 ms |

Le README historique d'altbak.pub donne **269,606 ms pour RBTree et 381,61 ms au total**. Les résultats intégrés sont donc environ **10,9–11,2 % sous cette référence RBTree** et **6,0–6,9 % sous son total**. La comparaison contrôlée ci-dessus isole mieux le changement que la référence historique ; la charge des autres processus de la machine peut varier.

`altbak.pub-phpurs/bin/php/run -c` passe également avec le backend reconstruit, les quatorze sorties et un total ponctuel de **350,76 ms**. Cette valeur isolée n'est pas retenue pour annoncer le pourcentage de gain.

Données : [révisions et empreintes des sources](source-state.json), [full-results.json](full-results.json), [journaux du build](integrated-build.log), [tailles](sizes.json).

## Vérifications

- `TerminalInlining` et neuf fixtures existantes passent avec le fork TAST : captures, slots de branches, initialisation de dictionnaire récursif, ombrage et cas TCO. Voir [fixtures.json](fixtures.json).
- `tests/codegen/tail-inline.mjs` exécute le PHP AST avant/après : ordre et unicité d'évaluation, argument inutilisé, jonction observée par capture par référence, collision de noms, application partielle, surapplication, exception, contrôles de types PHP, rejet des fonctions récursives/non fermées/non terminales et budgets.
- `branch-slots.mjs` (cinq scénarios) et `unary-callables.mjs` passent.
- Les empreintes des arbres sont identiques après chaque insertion sur les ordres croissant, décroissant, mélangé avec une graine fixe, et avec doublons : **1 285 insertions**, plus l'arbre vide. Voir [structural-checks.json](structural-checks.json).
- Le comptage est effectué séparément, sans JIT, sur une action RBTree complète. Voir [allocation-counts.json](allocation-counts.json).

| Construction ou appel | Témoin | Intégré |
| --- | ---: | ---: |
| R | 199 979 | 199 979 |
| B | 299 957 | 299 957 |
| E | 200 002 | 200 002 |
| T | 2 583 932 | 2 583 932 |
| ins | 2 283 976 | 2 283 976 |
| balance | 2 183 976 | 0 |

Le runner complet `bin/test` dépend du paquet `phpurs-math`, absent de ce checkout. Le runner isolé emploie les paquets frères disponibles pour ces dix fixtures et vérifie la présence de `typeTable`/`dataDecls`. Cela ne vaut pas validation de toute la suite ni des chantiers numériques/FFI encore ouverts.

## Coût et suite

Le module RBTree grossit de **42 830 à 69 913 octets** (+27 083 octets, environ +63 %). Sur les 299 modules, cela représente **+0,31 %** de PHP. Le pic mémoire mesuré pendant les quatorze benchmarks reste à **42 MiB** dans les quatre processus. Le budget limite l'expansion ; cette première passe sélectionne seulement RBTree dans la suite actuelle.

R9 est intégré pour ce périmètre. Élargir aux cibles typées, aux fonctions non feuilles ou aux appels non terminaux demanderait des preuves et mesures distinctes. LazyEvaluation reste vers 98–100 ms ici ; R8 conserve le potentiel de gain suivant, avec sa fixture de frontière FFI comme première étape. Les 2,58 millions de constructions de T restent un autre coût important, mais leur suppression requiert une preuve d'absence d'alias supplémentaire.

Reproduction depuis la racine PHPurs, après `bin/php/run -c` dans le worktree altbak.pub :

```sh
node tests/codegen/tail-inline.mjs
node tests/codegen/branch-slots.mjs
node tests/codegen/unary-callables.mjs
python3 audit/2026-09-08/r9/run-fixtures.py
node audit/2026-09-08/r9/prepare-comparison.mjs
python3 audit/2026-09-08/r9/compare.py
python3 audit/2026-09-08/r9/count-allocations.py
```
