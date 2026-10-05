# R14 — Seeds lisant une capture scalaire pure

Le 5 octobre 2026. Micro-étape intégrée dans
[`ThunkFusion.purs`](../../../src/Phpurs/ThunkFusion.purs), après la
[profondeur dynamique](../../2026-09-09/dynamic-thunks/report.md).

## Résultat

Un seed `\_ -> value`, où `value` est un local `Int` déjà évalué, est désormais
fusible. Sur le [programme PureScript](bench.purs) à profondeur **et** valeur
initiale fournies à l'exécution, les médianes passent de **46,738–47,858 ms** à
**0,837–0,950 ms** pour un million d'étapes. La médiane des deux médianes par
variante baisse de **98,11 %**. Le résultat est toujours **1 011 000**.

| Observation | Avant | Intégré |
| --- | ---: | ---: |
| Médianes des processus ABBA | 46,738 / 47,858 ms | 0,837 / 0,950 ms |
| Nœuds de thunks construits | 1 000 000 | 0 |
| Invocations de ces nœuds | 1 000 000 | 0 |
| Closures du seed construites / appelées | 1 000 / 1 000 | 0 / 0 |
| Additions du pas | 1 000 000 | 1 000 000 |
| Appels du garde / du worker | 0 / 0 | 1 000 / 1 000 |
| Pic mémoire PHP allouée | 4 MiB | 4 MiB |
| Taille PHP du programme, dépendances et entrée incluses | 5 712 992 octets | 5 714 683 octets |

Les comptes viennent de copies instrumentées séparées, JIT/OPcache désactivés.
Les mesures temporelles utilisent les fichiers générés sans instrumentation.
Le constructeur public `grow` est identique octet pour octet ; seul
`Main/index.php` change parmi les 245 fichiers PHP générés pour ce programme.
Le fichier de mesure ajouté porte la comparaison à 246 fichiers.

## Preuve et idempotence

- Le type du seed reste `Unit -> Int`, avec exactement un argument et un corps
  annoté `Int`. Le corps peut être un littéral entier ou une simple lecture locale.
- Le scanner transporte les bindings disponibles par couple **nom/niveau**.
  Paramètres curried/uncurried et résultats stricts de `Let`/`EffectBind` sont
  disponibles dans leur corps. Les définitions `LetRec` sont exclues, y compris
  dans leur continuation : l'annotation seule ne prouve pas leur initialisation.
- Le paramètre propre du thunk, une lecture hors portée, un champ mutable, une
  opération arithmétique ou un appel ne constituent pas un seed scalaire prouvé.
  Les valeurs produites par une FFI peuvent être utilisées après leur évaluation
  et leur binding ; cet appel reste à sa place et n'est pas dupliqué.
- Les preuves du constructeur, du pas uniforme, du `Unit` canonique et les
  budgets restent celles de R11/R14. Chaque étape conserve son opération PHP,
  notamment son comportement après débordement.
- Le garde sélectionne une fois le worker pour une profondeur non négative.
  Son repli reconstruit le seed capturant le scalaire et appelle le constructeur
  d'origine. **Construction et force sont deux applications imbriquées dans le
  TAST généré.** La reconnaissance des consommateurs exige une application aplatie
  à trois arguments : le repli ne devient donc pas candidat à sa propre fusion.
  Le générateur PHP réaplatit ensuite les appels. Trois rescans successifs
  conservent le même AST sans nouveau worker ; aucun test de préfixe réservé
  n'est nécessaire pour obtenir cette propriété.

## Protocole comparatif

[`freeze.mjs`](freeze.mjs) capture deux bundles exécutables avec **322 entrées JS
identiques**. Seul `output/Phpurs.ThunkFusion/index.js` est remplacé par sa version
antérieure pour le témoin. Les deux variantes incluent donc les mêmes versions
actuelles de PBO et des autres passes. Le CLI distribué est reconstruit avec
`npm run build` ; les bundles d'audit invoquent `Main.main` et compilent avec
`--no-cache`, ce qui écarte les identités de cache de la comparaison.

[`measure.py`](measure.py) compile une seule fois le programme en TAST, puis
génère son PHP avec chaque bundle. Il régénère aussi les **306 modules** du TAST
de la suite habituelle avec les deux variantes. Les **647 fichiers d'entrée**
CoreFn/FFI/Composer suivis sont revérifiés après les mesures ; le vendor verrouillé
est également contrôlé. Les empreintes, versions, échantillons et résultats sont
dans [`results.json`](results.json).

Chaque variante du noyau reçoit `repeats = 1000`, `depth = 1000`, `seed = 11` et
`acc = 0`. L'entrée chronométrée est `Main.run`, après chargement des modules ; la
compilation et l'initialisation sont hors intervalle. Chaque processus vérifie
aussi 24 paires profondeur/seed, puis effectue trois échauffements et dix mesures
avec `hrtime(true)`, en vérifiant le résultat après chaque appel. Quatre processus
frais sont exécutés en ordre **avant / intégré / intégré / avant**.

PHP **8.5.4**, JIT **1255**, buffer **128 MiB**, OPcache CLI actif, cache fichier
désactivé, Xdebug absent de cette installation. L'état JIT effectif est vérifié
par chaque processus. Node **24.8.0**, Spago global **1.0.3**, compilateur TAST
**0.15.16** du fork local ; `npm run build` utilise son outillage hôte local.
Machine partagée : les plages des processus sont conservées, sans extrapolation
à d'autres programmes.

## Suite habituelle et régressions

Les **307 fichiers PHP** de la suite habituelle, soit **7 373 140 octets**, sont
identiques entre variantes. Les quatre exécutions ABBA valident chacune les
**14 valeurs** avec l'oracle du driver. Totaux internes : **69,307 / 68,800 ms**
avant et **68,486 / 69,453 ms** avec l'extension. Ce corpus identique ne démontre
aucun gain runtime de la nouvelle reconnaissance.

- `npm run build` réussit. La reconstruction initiale du PBO courant signalait
  trois avertissements préexistants ; celle de l'extension rapporte zéro
  avertissement et zéro erreur.
- `npm run test:codegen` : **83 contrôles passent**. La régression ThunkFusion
  couvre captures statiques/dynamiques, portées, annotations, récursion locale,
  alias de niveaux, idempotence, noms frais, budgets, ordre et unicité des appels
  FFI, identité des exceptions et parité arithmétique.
- Les profondeurs négatives sont vérifiées en instrumentant uniquement la cible
  de repli, car le décompte original négatif ne termine pas. Quatre scalaires
  capturés sont transmis correctement ; le seed reste une `Closure` réutilisable
  et l'exception sentinelle conserve son identité.
- **Six fixtures passent en PHP et JavaScript** : `ImmediateThunkFusion`,
  `RecursiveDictionaryInitialization`, `CompactClosureLoops`,
  `FunctionFFIBoundary`, `PartialBindings`, `UnaryCallableCaptures`.
- `ImmediateThunkFusion` vérifie notamment le seed opaque évalué une seule fois,
  le scalaire courant d'une boucle TCO, les appels répétés d'une closure conservée
  et sa représentation à la frontière FFI.
- `altbak.pub-phpurs/bin/php/run` reconstruit le programme complet et valide ses
  14 valeurs avec le CLI distribué ; Composer réutilise ses dépendances vérifiées.
- [`count.py`](count.py) vérifie le travail réellement exécuté et l'identité du
  constructeur public ; [`summarize.py`](summarize.py) revalide les empreintes
  avant de publier les résultats.

## Reproduction et état

Commencer sur la version précédant l'extension : `npm run build`, puis conserver
`output/Phpurs.ThunkFusion/index.js` sous `$ARTIFACTS/baseline-thunk-fusion.js`.
Appliquer l'extension et reconstruire avec `npm run build`. Depuis PHPurs :

```sh
node audit/2026-10-05/scalar-seeds/freeze.mjs "$ARTIFACTS"
```

Après la compilation normale de la suite et les régressions, exécuter :

```sh
python3 audit/2026-10-05/scalar-seeds/measure.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-05/scalar-seeds/count.py --artifacts "$ARTIFACTS"
```

`measure.py` utilise le répertoire source jetable de `tests/runner`, ainsi que le
TAST et le vendor préparés par le driver des benchmarks. Il exige des répertoires
de sortie neufs. `summarize.py` attend en plus les relevés de validation de cette
campagne (`baseline.json`, `fixtures.json`, `codegen.log`).

État de départ : PHPurs `195cd881af39e9a20702f4c8c677ebe96d1c2eb7`, PBO
`157a544f0a469c7b0137a3fca66e626d717db493`, benchmarks
`be2b32d564a72fbafc429373697872b0a66e3c4c`. Les artefacts complets sont dans
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-r14-scalar-seed-20261005`.
Le relevé durable inclut les empreintes des sources modifiées et du CLI distribué.

La micro-étape des captures scalaires de R14 est terminée. La prochaine entrée
du backlog concerne la mesure des chaînes State immédiatement exécutées.
