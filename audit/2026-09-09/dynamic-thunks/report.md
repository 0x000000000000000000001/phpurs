# R14 — Fusion des thunks à profondeur dynamique

Le 9 septembre 2026. Extension intégrée dans `src/Phpurs/ThunkFusion.purs`, validée avec `altbak.pub-phpurs/bin/php/run -c`.

## Changement

Une chaîne fermée de thunks `Unit -> Int`, immédiatement forcée, peut désormais être fusionnée lorsque sa profondeur est une variable locale annotée `Int`. La valeur initiale reste littérale. Les conditions existantes sur le constructeur, le pas uniforme et le `Unit` canonique restent nécessaires.

Un garde privé `__phpurs_force_*` teste la profondeur une seule fois. Pour une valeur non négative, il appelle le worker scalaire `__phpurs_fuse_*` déjà utilisé par les consommateurs statiques. Pour une valeur négative, il appelle le constructeur public original, avec le seed et le `Unit` correspondants. Le worker arithmétique n'a pas été changé.

Les expressions arbitraires de profondeur ne sont pas déplacées ni dupliquées. Les callbacks inconnus, les seeds capturants et les fonctions conservées gardent leur chemin antérieur. Les gardes ne sont pas enregistrés dans les globals publics. Les deux familles de noms sont fraîches, y compris sans distinction de casse. Au maximum 32 constructeurs sont sélectionnés, chacun avec un worker et, si nécessaire, un garde.

## Mesures du changement intégré

Le programme [bench.purs](bench.purs) effectue 1 000 répétitions d'une chaîne dont la profondeur runtime vaut 1 000. Le seed vaut zéro. Il est compilé depuis le TAST avec deux bundles partageant les mêmes dépendances actuelles : seul le module JavaScript compilé de `ThunkFusion` est remplacé par sa version antérieure pour le témoin. Il ne s'agit plus d'un prototype PHP manuel.

PHP 8.5.4, JIT 1255, buffer 128 MiB, OPCache CLI actif, cache disque désactivé, Xdebug désactivé. Quatre processus frais en ordre ABBA. Après trois échauffements, chaque mesure comporte dix appels; `hrtime` entoure l'appel à la fonction générée qui effectue tout le travail.

| Passage | Variante | Meilleur temps | Médiane |
| --- | --- | ---: | ---: |
| 1 | Avant | 46,835 ms | 47,190 ms |
| 2 | R14 | 0,820 ms | 0,827 ms |
| 3 | R14 | 0,851 ms | 1,000 ms |
| 4 | Avant | 47,015 ms | 47,519 ms |

Cela correspond à environ 48–57 fois moins de temps sur ce programme, selon la paire de médianes. Le résultat vaut toujours 1 000 000. Les comptes séparés des chronométrages donnent **1 000 000 allocations de thunks avant, zéro après**, et **1 000 000 additions dans le worker après**. Aucune formule ne remplace les additions. Le champ `additions: 0` du témoin dans `counts.json` indique que ce compteur n'instrumente que le worker fusionné, pas que le témoin n'effectue aucune addition.

## Suite historique et référence native

Les **302 fichiers PHP sont identiques octet par octet** entre les deux versions de la passe sur le corpus actuel. Le benchmark Lazy utilise déjà une profondeur littérale et bénéficiait de R11.

Les quatre exécutions ABBA de la suite donnent des totaux de 126,114 / 123,607 / 123,281 / 123,185 ms, avec les 14 résultats corrects. Puis la commande complète `bin/php/run -c` termine avec le code 0 et un total de **120,61 ms**, Lazy à **796 µs**.

La référence officielle du README principal d'altbak.pub est **119,69 ms** au total, **785 µs** pour Lazy. La dernière colonne native donne respectivement **200,45 ms** et **368 µs**. Ces chiffres concernent le corpus historique, pas le nouveau diagnostic à profondeur dynamique. La variation du total est du bruit de mesure et ne constitue pas un gain attribuable à R14.

## Validations

- Neuf suites AST passent : branch slots, compact loops, copy cleanup, enum regions, nullable constructors, partial bindings, tail inline, thunk fusion et unary callables.
- Le test de fusion couvre les profondeurs dynamiques, les annotations, les formes refusées, le partage du worker, les budgets, les collisions et l'idempotence.
- Une profondeur négative ne termine pas sur le constructeur original. Le test instrumente donc uniquement la cible de repli pour vérifier la branche, les arguments, l'appel unique et la propagation de la même exception, sans exécuter une boucle infinie.
- Six fixtures ciblées passent en PHP et JavaScript : ImmediateThunkFusion, PartialBindings, CompactClosureLoops, FunctionFFIBoundary, TerminalInlining et CopyCleanup.
- La fixture ImmediateThunkFusion contient un vrai consommateur dynamique compilé depuis le TAST, vérifie 0, 1 et 1 000 étapes, l'évaluation unique d'une profondeur calculée par le FFI, les closures conservées, les appels répétés du seed et la frontière PHP Closure.
- Les comparaisons arithmétiques existantes, dont les effets de débordement et d'arrondi PHP, restent vertes.
- `git diff --check` passe. Des avertissements préexistants restent dans les sources du compilateur; aucun avertissement ne vient de la modification de ThunkFusion.

## Limites et suite

Cette étape ajoute la profondeur locale dynamique, pas une fusion générale des closures. Le TAST fournit le type de la profondeur; la sélection du constructeur et du seed fournit les autres preuves.

Prochaine étape distincte : étudier les seeds lisant seulement une capture scalaire pure. Cette extension devra préserver l'idempotence, puisque le seed du repli actuel capture le paramètre du garde. Les chaînes State et la fusion des collections restent des chantiers ultérieurs.

Les résultats bruts, le programme de diagnostic et les harnais de mesure/comptage sont conservés dans ce dossier. [state.json](state.json) indique les révisions, empreintes et le dossier temporaire contenant les bundles appariés, sorties générées, scripts de préparation et logs complets. La compilation initiale directe a contourné un accès refusé au cache SQLite global de Spago; la validation finale a utilisé avec succès la commande normale complète.
