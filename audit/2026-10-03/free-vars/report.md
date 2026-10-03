# B4 — réutilisation de l'analyse des variables libres

Date : 3 octobre 2026. Quatrième micro-étape B4, après l'accumulateur linéaire.

## Coût identifié

`closureCaptures`, `extractUncurriedAbs` et les enveloppes de conditions/effets
demandaient à nouveau `freeVars` sur des sous-arbres déjà parcourus. Les usages
de `TcoAnalysis` contiennent aussi les variables liées : ils ne constituent pas
directement l'ensemble libre requis pour les clauses PHP `use`.

Une sonde dans un bundle séparé compte chaque entrée dans l'analyse et les
identités `TcoExpr` déjà rencontrées, pendant une traduction réelle :

| Module | Visites avant | Nœuds distincts / visites après | Revisites après |
| --- | ---: | ---: | ---: |
| `Inter.Cli.Logic.Registry` | 46 114 | 6 450 | 0 |
| `Inter.Api.Registry` | 26 902 | 2 861 | 0 |
| `Core.Feat.Review.Message.Query.SearchArticles.Projection.Projection` | 56 855 | 26 701 | 0 |
| `Data.CodePoint.Unicode.Internal` | 2 114 | 757 | 0 |

Le profil CPU du témoin sur quinze traductions de `Inter.Cli.Logic.Registry`,
après échauffement et avec un intervalle d'échantillonnage de 250 µs, attribue
**476,875 ms inclusives** à `freeVars` et ses descendants, sur **2 560,583 ms**
d'échantillons, soit environ **18,6 %**. Ce profil localise le coût ; les mesures
de gain ci-dessous utilisent des processus sans instrumentation.

## Changement intégré

- PBO expose `freeVarsWith`, une étape d'analyse dont l'appel récursif est fourni
  par l'appelant. Son `freeVars` existant utilise la même étape.
- `Phpurs.FreeVars` mémorise cette récursion dans une `WeakMap`, indexée par
  l'identité du nœud immutable. Les appels récursifs consultent eux aussi la table.
- `CodeGen` utilise cette analyse partagée à tous ses points de demande.

Les règles de portée restent centralisées dans PBO : paramètres des lambdas,
portée du corps de `Let`/`EffectBind`, portée mutuelle de `LetRec`, enfants des
effets et transparence de `Typed`/`TypeApp`. La valeur mémorisée est un ensemble
immutable d'IDs locaux d'origine. Le renommage PHP et la capture par référence
sont appliqués ensuite, dans le contexte courant de chaque utilisation.

Le contrat détaillé est dans [compiler.md](../../../docs/compiler.md). Chaque
traduction de module construit de nouveaux nœuds TCO. Les ensembles stockés ne
référencent pas ces nœuds ; les clés faibles permettent leur collecte dès que
les références de traduction disparaissent. La table vit dans le processus JS,
indépendamment du stockage des modules et du budget RAM des implémentations PBO.

## Régressions

`npm run build` réussit avec **zéro avertissement et zéro erreur**.
`npm run test:codegen` réussit ses **83 contrôles**, dont cinq nouveaux :

- ensembles attendus explicites pour les formes syntaxiques et les binders,
  confrontés à l'analyse PBO et à la version mémorisée ;
- requêtes imbriquées et sous-arbres partagés, ensembles des enfants préservés
  après filtrage par un binder et absence de nouveaux parcours sur requête chaude ;
- même nœud de closure traduit avec des noms et captures valeur/référence
  différents, avec exécution de closures récursives indépendantes en PHP ;
- mêmes nœuds d'effets typés traduits dans plusieurs contextes, construction
  différée, ordre des effets et réutilisation vérifiés en PHP ;
- collecte effective de **2 049 nœuds** et du résultat non vide, dans un processus
  `--expose-gc` qui conserve la fonction mémorisée vivante.

La sonde déterministe de recomputation effectue des requêtes sur **193 nœuds**
imbriqués. Les lectures de syntaxe passent de **148 996 à 1 540**, sous une borne
linéaire de **6 176**. Le témoin échoue à cette borne. Une étape peut lire plusieurs
fois les champs du même objet dans le code de pattern matching généré ; cette
sonde mesure ces lectures, distinctes des visites de la première table.

## Traduction isolée de modules réels

Quatre `BackendModule` typés figés sont restaurés avec leurs constructeurs.
Ils servent de fixtures de mesure. Trois paires de processus suivent les ordres
avant/après, après/avant, avant/après ; chaque module reçoit quatre traductions
d'échauffement puis quinze mesures. Chargement et empreinte de l'AST PHP sont
hors horloge. Chaque appel à `CodeGen.translate` reconstruit son arbre TCO : les
échantillons mesurent la réutilisation au sein d'une traduction.

| Module | Médiane des trois médianes avant, ms | Après, ms | Réduction |
| --- | ---: | ---: | ---: |
| `Inter.Cli.Logic.Registry` | 155,380 | 124,349 | 19,97 % |
| `Inter.Api.Registry` | 84,842 | 72,652 | 14,37 % |
| `Core.Feat.Review.Message.Query.SearchArticles.Projection.Projection` | 91,952 | 83,721 | 8,95 % |
| `Data.CodePoint.Unicode.Internal` | 32,200 | 32,043 | 0,49 % |

Les trois premiers modules s'améliorent dans chacune des trois paires. Unicode
varie dans les deux sens, sans gain net établi. Les **quatre empreintes d'AST PHP
sont identiques** entre variantes, sondes et répétitions.

## Coût mémoire

La sonde dédiée garde les racines TCO d'un module réel vivantes après traduction.
Elle mesure le tas après GC forcé, puis remplace uniquement la table mémo du
bundle de diagnostic et mesure à nouveau, avec les mêmes racines vivantes.
La différence isole le coût retenu de la table et des ensembles associés. La
table est fraîche avant cette mesure par module ; le témoin sans mémo donne une
dérive comprise entre −32 et +200 octets.

Une traduction distincte observe ensuite, par `WeakRef`, chaque clé calculée et
chaque ensemble non vide distinct. Après abandon des racines et passages GC
dans de nouveaux tours de boucle, **aucune clé et aucun ensemble observé ne
restent vivants**, sans réinitialiser la table. Les références de la sonde sont
ensuite abandonnées pour mesurer séparément sa capacité résiduelle.

| Module | Coût mémo avec TCO vivant, octets | Ensembles non vides distincts | Plus grand ensemble | Table résiduelle après collecte, octets |
| --- | ---: | ---: | ---: | ---: |
| Projection | 1 277 392 | 1 963 | 20 | 1 048 560 |
| Unicode | 53 440 | 180 | 7 | 32 704 |
| API Registry | 199 632 | 486 | 14 | 130 928 |
| CLI Registry | 368 336 | 825 | 8 | 262 136 |

Le coût vivant observé va de **52,2 Kio à 1,22 Mio**. V8 peut conserver la capacité
allouée d'une table faible après collecte de ses clés ; la valeur résiduelle
dépend de l'historique des traductions. Ces mesures ne constituent pas un plafond.
La mémorisation borne les parcours répétés de syntaxe, mais les opérations sur
ensembles restent sensibles à leurs cardinalités et leur stockage peut devenir
superlinéaire sur des arbres pathologiques. Les pics RSS des builds complets
ci-dessous contrôlent aussi le comportement avec le corpus entier.

## Compilation complète b8x

Le driver [verify.py](../linear-accumulator/verify.py) est réutilisé en ordre
**avant, après, après, avant**, avec les mêmes sorties PHP préexistantes et les
mêmes mtimes anciennes. Node v24.8.0, `GOPURS_JOBS=1`, budget PBO par défaut
64 Mio, options `--main Inter.Api.Main --bundle --no-cache --profile-build`.

| Essai | Processus, s | Traduction totale, s | CLI Registry, ms | Pic RSS, Kio |
| --- | ---: | ---: | ---: | ---: |
| 1 — avant | 50,662 | 6,824 | 181,669 | 3 716 400 |
| 2 — après | 47,073 | 5,571 | 164,367 | 3 497 584 |
| 3 — après | 47,203 | 5,470 | 152,880 | 3 787 776 |
| 4 — avant | 48,068 | 6,578 | 180,789 | 3 698 112 |

Les quatre builds terminent leurs **2 684 modules**, conservent **5 372 fichiers
identiques** et les **2 686 mtimes PHP**, avec **zéro réécriture PHP**. Le manifeste
des entrées et le corpus immuable sont vérifiés avant/après.

La médiane de traduction globale passe de **6,701 à 5,520 s**, soit **−17,62 %**
sur ces quatre builds. Les temps de processus sont aussi plus bas pour les deux
variantes, mais l'optimisation et les I/O varient également ; les deux observations
par version ne suffisent pas à fixer un gain général de bout en bout. Les pics
RSS se recouvrent et ne démontrent pas de réduction mémoire globale.

Un premier essai avait échoué avec `ENOSPC` pendant la publication `.purmeta` du
deuxième build. Ses journaux restent dans `validation-enospc`, hors des mesures
retenues. Le nettoyage demandé de l'arbre `phpurs` a retiré **2 205 388 781 octets**
d'anciens backups/cache (**31 831 entrées**), ainsi que les métadonnées Finder.
Les **2 660 caches versionnés** ont été supprimés et dix `.gitignore` complétés.
Le protocole ABBA a ensuite été repris intégralement.

## Benchmarks exécutables

[benchmark.py](benchmark.py) copie le workspace `pure` à sa profondeur relative
d'origine et conserve son lien vers les bibliothèques FFI. Il vérifie les CoreFn,
FFI adjacentes, entrées du driver, dépendances Composer verrouillées et le lien
du package généré vers `output`.

Les deux CLIs régénèrent les **350 mêmes artefacts**, dont **307 PHP**, et le
validateur `core` confirme les **14 valeurs attendues** dans les deux exécutions.
Le stockage OPcache est recréé avant chaque processus, avec JIT `1255`, tampon
128 Mio et Xdebug désactivé. Les sommes observées de **72,070 et 70,709 ms** sont
des contrôles de résultats sur un PHP identique ; elles ne mesurent pas un gain
runtime apporté par l'analyse du compilateur.

## Provenance et reproduction

Base PHPurs : `8d8295456101a5eea908161bd8d02112275ff703`, plus l'accumulateur de
la micro-étape précédente. Base PBO : `c9386b4d572503bb7b6d1ce9547ec30dcded2920`.
Le changement courant touche les deux dépôts.

- CLI avant : `358bd327a0ea245be51c137e7914af4707e321d2f16dedb741ec8ef44bcfaaa9`.
- CLI après : `42e83167759c9f70838330670994eb3825283c64063d07d68b2b45758a229c40`.
- Corpus : 2 839 fichiers, manifeste
  `9e159394a364c95af49aa1d66cdcc7d3c66ec410acbaf70b19f96bfda98e7372`.

Les [résultats compacts](results.json) contiennent les médianes par processus,
empreintes des fixtures et AST, données mémoire, phases des builds et résultats
runtime. `summarize.py` les reconstruit en vérifiant les preuves brutes.

```sh
node audit/2026-10-03/free-vars/freeze.mjs /artefacts/library.mjs
node audit/2026-10-03/linear-accumulator/module-probe.mjs \
  /artefacts/library.mjs /artefacts/states.json /artefacts/modules.json
node audit/2026-10-03/free-vars/freeze.mjs /artefacts/visits.mjs --visits
node audit/2026-10-03/free-vars/visits.mjs \
  /artefacts/visits.mjs /artefacts/states.json /artefacts/visits.json
node --expose-gc audit/2026-10-03/free-vars/memory.mjs \
  /artefacts/library.mjs /artefacts/states.json /artefacts/memory.json
node audit/2026-10-03/free-vars/recomputation.mjs \
  /artefacts/library.mjs /artefacts/recomputation.json
```

Le protocole complet utilise les arguments de `verify.py` décrits dans le
[bilan précédent](../linear-accumulator/report.md#provenance-et-reproduction),
avec les nouveaux CLIs. `benchmark.py --help` précise les chemins du workspace
figé, du driver et des CLIs. Artefacts locaux de cette étape :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b4-free-vars-8w0ihqyv`.
