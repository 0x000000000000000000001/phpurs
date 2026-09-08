# R0 : réutilisation des variables terminales de branches

La première optimisation est intégrée à PHPurs. Le PHP de `balance` passe de **249 à 33 variables distinctes**. Une comparaison avec le snapshot avant optimisation confirme que **seuls les noms des binders changent dans cette fonction** : branches, appels et expressions de construction restent identiques.

## Résultats

Mesures prises par le `Bench.purs` existant : trois échauffements, dix passages, minimum par test. Même entrée RBTree de 100 000 insertions et mêmes 14 sorties attendues. PHP 8.5.4, OPcache CLI, JIT 1255 et buffer 128 Mio ; Xdebug absent des deux confirmations, statut JIT actif enregistré.

| Exécution | RBTree (ms) | Somme des 14 tests (ms) |
| --- | ---: | ---: |
| Avant R0, PHP original | 693,896 | 816,808 |
| Après reconstruction complète | 287,907 | 407,670 |
| Confirmation 1 | 296,565 | 417,580 |
| Confirmation 2 | 287,723 | 411,046 |
| README historique d'altbak.pub | ~269,606 | ~381,610 |

Cela représente environ **57–59 % de temps en moins sur RBTree** et **49–50 % sur la somme**, par rapport à l'exécution avant R0 de cette session. La machine est partagée : ces fourchettes décrivent les relevés, pas un intervalle statistique. Le profil précédent donnait aussi 644,6–677,4 ms pour le code original et 282,6–283,5 ms pour le renommage diagnostique. Les résultats restent proches de cette expérience.

Le README historique reste meilleur : ce changement récupère une grande partie de l'écart constaté au début du chantier. Ce n'est pas un nouveau record historique. La somme additionne les minima des tests ; elle n'est pas une durée de processus et exclut compilation et Composer.

Les données détaillées et métadonnées sont dans [results.json](results.json), avec les quatre journaux voisins.

## Changement limité

`src/Phpurs/CodeGen.purs` réutilise un nom dédié `__local_var_<niveau>_slot` uniquement pour un `Let Nothing` :

- en position terminale ;
- hors contexte de boucle TCO ;
- dont la valeur est un local, une lecture de champ ou un littéral scalaire (les enveloppes Typed/TypeApp sont traversées) ;
- dont le binding n'est pas déjà présent dans la portée active.

Les arguments, opérandes, conditions et valeurs intermédiaires gardent leurs noms uniques. C'est nécessaire : le générateur peut exécuter les instructions de plusieurs arguments avant de consommer leurs expressions de résultat. Le compteur `nextId` continue d'avancer pour les labels, jonctions et autres bindings. Les slots restent distincts des paramètres et des noms récursifs capturés par référence.

Le TAST est conservé dans le pipeline ; cette première réduction de variables utilise la structure des portées. Le JSON RBTree régénéré contient bien `typeTable` et ses deux `dataDecls`. Les signatures, représentations et spécialisations guidées par les types restent des optimisations distinctes à mesurer.

## Validation

- `npm run build` réussit. Les avertissements existants du projet et des dépendances restent présents.
- `node tests/codegen/branch-slots.mjs` : cinq scénarios exécutent réellement le PHP produit par le générateur. Ils couvrent les opérandes frères, les branches terminales, une branche non terminale, un contexte TCO et un binding actif. Le cas `let 11` moins `let 22` doit retourner −11 ; une réutilisation naïve retourne 0.
- Huit fixtures passent avec le compilateur TAST : BranchBindingSlots, RecursiveDictionaryInitialization, DeepCase, ShadowedRename, ShadowedTCOLet, TCOCase, TCOMutRec et CaseMultipleExpressions. Voir [fixtures.json](fixtures.json).
- La nouvelle fixture vérifie les valeurs après jonction, les closures conservées de deux branches, une closure récursive et une boucle accumulant d'anciennes captures. Le PHP conservé dans `raw/BranchBindingSlots.php` montre le slot capturé par valeur et le nom récursif capturé séparément par référence.
- `bin/php/run -c` réussit dans altbak.pub-phpurs ; les 14 sorties correspondent à la référence sur les trois exécutions après changement.
- Revue ciblée des portées/captures et `git diff --check` sans problème relevé.

Le runner habituel `bin/test` exige le checkout absent `phpurs-math`. Les huit fixtures ont donc été exécutées dans un projet isolé avec leurs seules dépendances nécessaires ; aucune n'importe Math. [run-fixtures.py](run-fixtures.py) prépare et reproduit cette validation. Le runner général reste inchangé et la suite complète n'a pas été exécutée.

## Ajustement du script de reconstruction

Dans le worktree altbak.pub-phpurs, `bin/php/run -c` sélectionne explicitement le compilateur standard installé dans `run/bak/js/node_modules/purescript/purs.bin` pour reconstruire PHPurs en JavaScript. Un sous-shell temporaire place ce compilateur et le Spago local en tête de PATH, puis exécute les deux étapes build/bundle. Le dossier temporaire est nettoyé sans remplacer les traps du processus parent. Après cette étape, le script sélectionne le fork TAST pour les programmes destinés à PHP.

Le simple déplacement de l'export PATH après `npm run build` ne suffisait pas : le terminal utilisateur plaçait déjà `~/.local/bin/purs` (fork) en tête. L'erreur `EscapedSkolem` dans `Foreign.Object` a été reproduite dans cet environnement. Préfixer PATH avant npm seul n'est pas non plus une garantie, puisque npm préfixe ses propres dossiers `.bin`.

La commande corrigée a été exécutée deux fois avec `~/.local/bin` en tête du PATH appelant. La seconde validation a reconstruit les **417 modules de l'outil sans ses anciennes sorties**, après leur mise de côté temporaire. `Foreign.Object` est effectivement recompilé, les 14 sorties sont correctes sur les deux exécutions, et le JSON RBTree contient toujours les métadonnées TAST. Voir [host-path-validation.json](host-path-validation.json) et les journaux [avant](host-path-before.log), [après](host-path-after.log) et [reconstruction sans anciennes sorties](host-path-cold.log). Le compilateur TAST lui-même et les dépôts principaux n'ont pas été modifiés.

## Suite

Profiler LazyEvaluation, puis choisir son coût dominant. Reclasser ensuite les benchmarks. Les généralisations de R0, les retours directs et la simplification du decision tree ne sont pas cumulés avec cette étape.
