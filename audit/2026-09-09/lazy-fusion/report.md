# Lazy Evaluation : prototype de fusion construction/évaluation

Le prototype isolé supprime la matérialisation de la chaîne immédiatement forcée. **Il n'est pas intégré au générateur.** Les 301 modules PHP actifs restent identiques à leur état initial ; seules les copies sous `raw/` sont modifiées. Le constructeur public de thunks reste byte-identique dans la copie, avec sa représentation Closure et son protocole d'application partielle.

## Mesures

PHP 8.5.4, JIT 1255, tampon 128 MiB, Xdebug absent, aucun cache de fichiers OPcache. Processus neufs et successifs, ordre témoin/prototype puis prototype/témoin. Le runner utilise directement l'action générée et le protocole de `Bench.purs` : trois warm-ups puis meilleur de dix, horloge dans le programme. Les compteurs sont exécutés séparément, sans JIT.

| Mesure | Témoin actuel | Copie avec fusion |
| --- | ---: | ---: |
| Lazy isolé | 45,819–46,946 ms | 0,345–0,350 ms |
| Lazy dans les 14 tests | 46,959–47,062 ms | 0,347–0,372 ms |
| Total des 14 tests | 245,328–245,621 ms | 196,584–197,720 ms |
| Pic mémoire du processus complet | 34 MiB | 34 MiB |

Le total baisse de **19,50–19,87 %** dans ces paires. Lazy économise environ **46,6–46,7 ms** ; quelques millisecondes du delta total viennent des variations des autres tests, dont RBTree, dont le code reste identique. Les 14 sorties sont exactes à chaque run. Résultats : [bench-results.json](bench-results.json), [full-results.json](full-results.json).

Le README actuel du checkout principal `altbak.pub` donne **237,07 ms** au total et **44,911 ms** pour Lazy. Le témoin contemporain est un peu plus lent ; les pourcentages ci-dessus utilisent les paires contemporaines. La dernière colonne donne **0,368 ms** pour Lazy natif optimisé et **200,45 ms** au total. La fusion atteint le même ordre de grandeur que cette boucle native ; le total natif est historique, sans nouvelle comparaison native complète ici. Le README du worktree `altbak.pub-phpurs` garde un relevé antérieur, **316,45 ms / 50,331 ms**. Aucun de ces README n'est modifié par le prototype.

## Transformation exacte

L'appel ciblé construit une chaîne de profondeur 1 000, avec la fonction initiale connue `\_ -> 0`, puis la force immédiatement. Une fonction diagnostique privée calcule la fonction initiale une fois et exécute successivement les mêmes additions `+ 1`. La boucle extérieure de 1 000 répétitions, l'appel `Bench.opaque`, le calcul du résultat, les wrappers publics et les autres benchmarks sont conservés.

Il reste donc **1 000 appels à la fonction initiale et 1 000 000 additions**. Les **1 000 000 objets capturant le thunk précédent et leurs 1 000 000 invocations** disparaissent du trajet mesuré. [counts.json](counts.json) vérifie ces comptes à l'exécution ; aucun nombre de benchmark n'est remplacé par son résultat final.

Le worker respecte la distinction du code émis entre profondeur zéro, où l'argument Unit fourni est transmis directement, et profondeur positive, où les maillons utilisent le Unit global. Une profondeur négative conserve le chemin du constructeur original au lieu de devenir une boucle terminante. Ce cas divergent n'est pas exécuté par la validation ; le contrôle d'entrée le refuse pour une future fusion.

## Types, validation et limites

Le TAST confirme une profondeur littérale positive, une fonction initiale `Unit -> Int` dont le corps est le littéral zéro, et un résultat forcé `Int`. Sa signature aplatie du constructeur est `(Int, Unit -> Int, Unit) -> Int` : les deux abstractions explicites construisent une fonction, puis le troisième argument la consomme. Une future passe doit conserver cette distinction d'arité. [tast-evidence.json](tast-evidence.json) conserve ces informations.

`inspect-tast.py` vérifie le site de ce programme figé et refuse six mutants : profondeur négative/dynamique, fonction initiale inconnue, corps appelant une FFI, annotation de fonction absente et résultat Any. Ce diagnostic utilise les identifiants du programme analysé ; **ce n'est pas une reconnaissance générique de compilateur**. La preuve du corps récursif et son lien avec le site après PBO restent à intégrer aux AST.

Les vérifications exécutées couvrent :

- 30 combinaisons de profondeur et de valeur initiale, avec comparaison exacte du type et de la valeur PHP ;
- l'ordre d'évaluation des arguments, un effet de la fonction initiale exécuté une seule fois et l'identité de son exception ;
- le comportement Unit à profondeur zéro/positive ;
- la construction publique différée, les appels partiels, deux forces d'une racine conservée après mutation d'un état partagé, la vraie FFI `Foreign` et un paramètre PHP typé `Closure`.

Deux contre-exemples sont conservés dans [validation.json](validation.json) :

1. Une fonction initiale qui inspecte `debug_backtrace()` observe la disparition des frames de thunks. Son type `Unit -> Int` ne suffit donc pas à autoriser la fusion. Les callbacks inconnus/FFI doivent rester sur le chemin actuel ; le site mesuré possède un corps littéral connu qui n'effectue aucune observation.
2. Remplacer toutes les additions par `seed() + depth` change l'arrondi après débordement des entiers PHP. Un test diagnostique avec `PHP_INT_MAX - 1` et profondeur 2 048 le montre. Cette valeur explore le comportement PHP au-delà des Int PureScript 32 bits ; le prototype conserve chaque addition et ne dépend pas d'une simplification algébrique.

Le prototype n'étend pas la transformation aux fonctions conservées, aux corps inconnus ou aux captures dépendant du numéro d'itération. Les tests d'effets ordinaires ne prouvent pas l'équivalence de tous les callbacks PHP possibles. Aucune compilation ni modification du code du générateur PHPurs/PBO, des packages partagés ou des sorties actives n'a été effectuée pour cette expérience.

## Reproduction et suite

Depuis le checkout PHPurs :

```sh
python3 audit/2026-09-09/lazy-fusion/prepare.py
python3 audit/2026-09-09/lazy-fusion/inspect-tast.py
php -d opcache.enable_cli=0 audit/2026-09-09/lazy-fusion/validate.php audit/2026-09-09/lazy-fusion/raw/fused/output ../phpurs-foreign/src/Foreign.php
python3 audit/2026-09-09/lazy-fusion/count.py
python3 audit/2026-09-09/lazy-fusion/measure.py bench
python3 audit/2026-09-09/lazy-fusion/measure.py full
python3 audit/2026-09-09/lazy-fusion/check-state.py
```

`prepare.py` exige le SHA256 exact du PHP étudié et modifie un seul appel dans une copie ; ce patch de texte est uniquement un outil diagnostique. [state.json](state.json) vérifie les 301 modules actifs inchangés.

La prochaine étape est une passe générique bornée dans PHPurs : reconnaître dans l'AST typé optimisé un constructeur récursif de chaîne immédiatement consommée, prouver le corps uniforme `force précédent + 1`, la fonction initiale connue pure et la profondeur admissible, puis émettre le worker scalaire au seul site fermé. Garder les API publiques et refuser tout cas non prouvé. Rejouer les contre-exemples, les régressions R8/R9/R10 et une comparaison après `bin/php/run -c` avant de compter ce gain comme livré.
