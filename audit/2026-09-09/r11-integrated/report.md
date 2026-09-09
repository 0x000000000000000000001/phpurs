# R11 : fusion des chaînes immédiatement forcées, intégrée

La passe `Phpurs.ThunkFusion` transforme le TAST optimisé avant TCO et génération PHP. Elle est activée dans le générateur normal ; `altbak.pub-phpurs/bin/php/run -c` réussit et les sorties actives contiennent le worker scalaire. Aucun nom de benchmark, profondeur particulière ou résultat final n'est codé dans la passe.

## Mesures après régénération

PHP 8.5.4, JIT 1255 actif, tampon 128 MiB, Xdebug absent, cache de fichiers OPcache désactivé. Quatre processus neufs et successifs, ordre témoin/intégration puis intégration/témoin, sans compilation ou instrumentation simultanée. Le témoin utilise exactement le même générateur et les mêmes TAST, avec seulement `ThunkFusion.optimize` remplacé par l'identité lors du bundling. Le chronomètre, les trois warm-ups et le minimum de dix restent ceux de `Bench.purs`.

| Suite complète | Témoin 1 | Intégration 1 | Intégration 2 | Témoin 2 |
| --- | ---: | ---: | ---: | ---: |
| Lazy | 46,672 ms | 0,783 ms | 0,828 ms | 47,147 ms |
| RBTree | 178,169 ms | 180,564 ms | 176,562 ms | 172,902 ms |
| Total des 14 tests | 243,727 ms | 200,029 ms | 196,111 ms | 238,733 ms |
| Pic mémoire du processus | 34 MiB | 34 MiB | 34 MiB | 34 MiB |

Lazy accélère de **56,9 à 59,6 fois** et économise **45,9 à 46,3 ms**. Le total baisse de **17,85 à 17,93 %** dans les paires contemporaines. Les variations de RBTree, dont le PHP reste identique, réduisent ici le delta total par rapport au gain propre à Lazy. Les 14 résultats sont vérifiés dans chaque run. Voir [full-results.json](full-results.json) et les journaux voisins.

Le premier `bin/php/run -c` donne 191,20 ms au total et 0,801 ms pour Lazy ; il valide la commande, mais les pourcentages utilisent les paires ci-dessus. La baseline officielle du README principal `altbak.pub` reste **237,07 ms / Lazy 44,911 ms**. Le README du worktree reprend le second run intégré, avec la provenance et l'ancienne référence.

La dernière colonne du README donne historiquement **0,368 ms** pour Lazy natif optimisé et **200,45 ms** au total. Lazy compilé reste donc environ **2,1 à 2,3 fois** plus lent que cette référence native, qui utilise une boucle d'incréments. Aucun nouveau run natif n'est fait ici : la proximité des totaux ne prouve pas une parité générale des backends.

Le [prototype précédent](../lazy-fusion/report.md) donnait 0,347–0,372 ms pour Lazy, avec une boucle PHP écrite directement. L'intégration réutilise le générateur TCO générique : copies de paramètres, branche, labels et protocole d'appel restent visibles. Elle enlève le même coût dominant, sans revendiquer le chrono du prototype. Simplifier ce code relève de R4, à mesurer séparément.

## Preuve et transformation

La signature TAST aplatie est `(Int, Unit -> Int, Unit) -> Int`, mais le constructeur doit posséder exactement **deux binders effectifs**. Le troisième argument consomme sa fonction résultat. La passe vérifie conjointement les types et la structure réelle après PBO :

- groupe récursif singleton ; à profondeur zéro, retour exact de l'accumulateur fonctionnel ;
- sinon, appel au même constructeur avec profondeur moins un et une fonction unary ;
- cette fonction force une seule fois le précédent avec le Unit canonique, puis applique une opération entière `+`, `-` ou `*` avec un littéral constant à droite ;
- consommation directe et immédiate avec trois arguments, profondeur entière littérale non négative, seed `Unit -> Int` au corps entier littéral, Unit canonique.

Les autres formes gardent leur AST : callbacks inconnus ou FFI, captures, pas dépendant de la profondeur, argument Unit observable, profondeur dynamique/négative, fonctions conservées, application partielle/indirecte/non aplatie, types inconnus et groupes mutuellement récursifs. Une profondeur négative est refusée statiquement ; le chemin original divergent n'est pas exécuté par les tests.

Un worker privé par constructeur sélectionné reçoit profondeur et valeur initiale scalaires. Chaque itération effectue l'opération d'origine ; aucun calcul `seed + profondeur`, aucune constante de résultat. Le seed littéral permet également d'éliminer ses appels, contrairement au prototype. Les noms évitent les collisions PHP sans distinction de casse, et les workers n'ajoutent aucune entrée `$GLOBALS` publique. La marche est bornée : preuve de 1 024 nœuds, profondeur 96, largeur 64, corps appelant de 8 192 nœuds, scan de 32 768 nœuds et au plus 32 workers.

Les compteurs séparés, sans JIT, donnent :

| Opérations par action Lazy | Témoin | Intégration |
| --- | ---: | ---: |
| Appels au constructeur | 1 000 | 0 |
| Objets capturant le précédent | 1 000 000 | 0 |
| Invocations de ces objets | 1 000 000 | 0 |
| Appels au seed littéral | 1 000 | 0 |
| Additions de la chaîne | 1 000 000 | 1 000 000 |

Voir [counts.json](counts.json). La boucle extérieure, `Bench.opaque` et les résultats attendus restent inchangés.

## Vérification

- Six suites AST : branch slots, unary callables, compact loops, terminal inlining, enum regions, thunk fusion.
- La nouvelle suite couvre la reconnaissance indépendante des noms, les refus structurels et typés, les budgets, les collisions, l'idempotence et les appels privés directs. Elle exécute 48 comparaisons numériques PHP exactes, dont multiplication après débordement, plus le contre-exemple d'addition à partir de `PHP_INT_MAX - 1` sur 2 048 pas. Cette dernière valeur teste le comportement PHP au-delà du domaine Int PureScript 32 bits.
- Une fonction initiale inconnue utilisant `debug_backtrace()` conserve ses frames. Les observations de construction différée, appels partiels, forces répétées après changement de capture et identité d'exception passent. L'ordre d'arguments observables est préservé par refus : seuls des littéraux, une fonction sans capture au corps littéral et le Unit canonique sont supprimés.
- Quatorze fixtures compilées avec le fork TAST et exécutées en PHP et JavaScript, avec sorties identiques : nouvelle `ImmediateThunkFusion`, R8/R9/R10, FFI/Foreign, captures, branches, dictionnaires récursifs, cas profonds, renommage et TCO. Voir [fixtures.json](fixtures.json). Le runner ciblé contourne la dépendance `phpurs-math` absente du runner global ; il ne prétend pas couvrir toute la suite du dépôt.
- Reconstruction complète : zéro erreur, zéro avertissement du programme benchmark. Le rebuild du backend rappelle 23 avertissements existants de `Main`/`CodeGen`, aucun dans `ThunkFusion`.
- Sur les **301 modules** PHP, seul `Test.LazyEvaluation` diffère entre les variantes ; son préfixe contenant le constructeur public est identique. Les sorties actives correspondent intégralement à la variante intégrée. Voir [state.json](state.json).

Les sources du benchmark, PBO et les paquets partagés ne sont pas modifiés. Aucune branche n'est mergée et aucun commit n'est créé.

## Reproduction

Depuis le checkout PHPurs, après `bin/php/run -c` dans le worktree benchmark :

```sh
node tests/codegen/thunk-fusion.mjs
python3 audit/2026-09-09/r11-integrated/run-fixtures.py
node audit/2026-09-09/r11-integrated/prepare-comparison.mjs
python3 audit/2026-09-09/r11-integrated/count.py
python3 audit/2026-09-09/r11-integrated/check-state.py
python3 audit/2026-09-09/r11-integrated/measure.py full
```

Les copies et instruments sont sous `raw/`, ignoré par Git. Les chiffres conservés proviennent du code émis, pas d'un patch du PHP actif. La priorité suivante est de mesurer la représentation privée `E = null` sur RBTree (environ 90 % du total restant), avec preuve de domaine fermé et préservation des frontières publiques avant intégration.
