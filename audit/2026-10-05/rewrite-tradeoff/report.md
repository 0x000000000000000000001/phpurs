# B4 — comparaison des limites de réécriture

Date : 5 octobre 2026. Dernière micro-étape B4.

## Décision

**Conserver le défaut de 10 000.** Sur les corpus figés, les limites suffisantes
produisent exactement les mêmes passes, tailles PHP et programmes exécutables.
La limite **11** suffit au corpus b8x ; **8** suffit au corpus des benchmarks.
Les limites immédiatement inférieures font échouer des optimisations terminantes.
Le relevé ne justifie donc aucun changement du garde de production.

La limite **100** obtient la meilleure médiane de compilation b8x de cette série,
mais les plages se recouvrent et la baisse à **11** ne poursuit pas cette
amélioration. Avec trois processus par réglage et aucun travail d'optimisation
supprimé, cet écart ne constitue pas un gain établi du réglage.

## Ce que contrôle le garde

Dans `PureScript.Backend.Optimizer.Semantics.optimize`, chaque binding commence
avec le garde demandé. `goStep` vérifie `n == 0` avant de travailler. Le chemin
itératif évalue/réifie l'expression ; si l'analyse demande encore une réécriture,
un nouveau passage utilise `n - 1`. Le dernier passage sans réécriture fait donc
partie du budget requis. L'épuisement lève `Possible infinite optimization loop.`

Lorsque la taille interne `analysis.size` dépasse 2 000, `optimizeChunk` traite
des sous-expressions puis termine ce passage. Ce chemin de découpage est
distinct du garde. Sa sélection et ses opérations ne dépendent pas de la valeur
positive restante. La taille interne PBO diffère du compteur complet de nœuds AST.

Le contrôle est un garde de convergence : une valeur suffisante autorise le même
calcul complet. Une valeur insuffisante produit une erreur, et non un résultat
moins optimisé dont on pourrait mesurer le compromis taille/runtime.

## Corpus et protocole

Les précédents artefacts temporaires n'étant plus présents, `prepare.py` capture
les CoreFn PHP existants et les dépendances adjacentes nécessaires dans un nouveau
snapshot : **2 684 modules b8x**, **306 modules de benchmark**, **3 166 fichiers
d'entrée** au total, avec leurs empreintes. Les chemins relatifs FFI et Composer
sont préservés. Les sources d'origine sont vérifiées inchangées après la campagne.

Un unique CLI autonome est reconstruit, figé et utilisé pour toutes les mesures :

- PHPurs : `dfe6a6618d7a82aa8149760761c9b36c5b49f805` ;
- PBO : `3af24d7f3909ba326ac46f3325cfd208139af286` ;
- SHA-256 CLI : `42e83167759c9f70838330670994eb3825283c64063d07d68b2b45758a229c40` ;
- manifeste des entrées : `f598d1981697777c10499d6b709b3d1d77c668233f892567e2d929feea155cd6` ;
- Node v24.8.0, `GOPURS_JOBS=1`, budget PBO par défaut de 64 Mio ;
- `--no-cache --profile-build --rewrite-limit N` ; main `Inter.Api.Main` et
  `--bundle` pour b8x, main `App` et sortie modulaire pour les benchmarks.

Le bundle de diagnostic ajoute des compteurs dans le JavaScript généré de PBO,
sans modifier ses sources. Il enregistre chaque passage et chaque appel de
`optimizeChunk`. Il active aussi `--verbose` pour le comptage AST complet.
Ses chronos sont exclus des mesures comparatives. Les mesures utilisent le CLI
autonome, sans ces compteurs ni le parcours de diagnostics AST.

Chaque corpus reçoit neuf builds dans l'ordre tournant :

1. **10 000, 11, 100** ;
2. **11, 100, 10 000** ;
3. **100, 10 000, 11**.

Chaque build utilise un processus frais. La référence est une génération du
snapshot à 10 000 avec le compilateur figé. Les fichiers PHP préexistants portent
ensuite la même mtime ancienne. Les contrôles I/O, empreintes et sources sont hors
horloge. Les compteurs vérifient la compilation de tous les modules, la publication
courante `.purmeta`, l'absence de cache persistant et les écritures PHP réelles.

## Passes réellement nécessaires

| Corpus | Bindings optimisés | Passages | Maximum par binding | Bindings avec découpage |
| --- | ---: | ---: | ---: | ---: |
| b8x | 31 859 | 45 109 | 11 | 41 |
| Benchmarks | 4 042 | 6 756 | 8 | 2 |

Distribution b8x : 24 397 bindings à un passage, 4 144 à deux, 1 375 à trois,
1 573 à quatre, 256 à cinq, 88 à six, 13 à sept, 11 à huit, un à neuf et un à onze.
Le maximum est atteint par
`Core.Feat.Review.Message.Query.GetArticleQuote.Projection.Projection.ordQuote`.

Sur les benchmarks, les maxima sont `Data.DateTime.Instant.fromDateTime`,
`Data.DateTime.Instant.fromDate` et `Data.Map.Internal.unsafeBalancedNode`.

Les traces à **10 000 et 11 sur b8x**, puis **10 000 et 8 sur les benchmarks**,
sont identiques après retrait du nombre restant dans le garde : ordre des
bindings, passages, tailles intermédiaires, indicateurs de réécriture et parcours
des chunks. Les comptes AST sont aussi identiques : **1 514 089** et **147 285**.

| Corpus | Garde | Résultat |
| --- | ---: | --- |
| b8x | 1 | Échec sur `Data.Void.absurd`, après 5 modules traduits |
| b8x | 10 | Échec sur `…GetArticleQuote.Projection.Projection.ordQuote`, après 715 modules |
| b8x | 11 | Succès des 2 684 modules |
| Benchmarks | 1 | Échec sur `Data.Void.absurd`, après 8 modules |
| Benchmarks | 7 | Échec sur `Data.DateTime.Instant.fromDateTime`, après 226 modules |
| Benchmarks | 8 | Succès des 306 modules et des 14 résultats runtime |

Les temps des builds échoués ne sont pas des gains de compilation. Leurs sorties
PHP préexistantes restent identiques ; aucun échantillon runtime n'est attribué
à ces exécutions échouées.

## Compilation et mémoire b8x

Médianes de trois processus par réglage ; la plage indique les trois temps de
processus observés. Les pics RSS sont également donnés en médiane.

| Garde | Processus, s | Plage, s | Optimisation, s | Traduction, s | Pic RSS, Kio |
| ---: | ---: | --- | ---: | ---: | ---: |
| 11 | 47,640 | 46,291–50,928 | 29,117 | 5,471 | 3 690 176 |
| 100 | 45,760 | 45,423–47,329 | 27,903 | 5,289 | 3 756 336 |
| 10 000 | 47,268 | 46,839–48,754 | 28,759 | 5,468 | 3 730 912 |

Les neuf builds conservent **5 372 fichiers identiques**, dont **2 686 PHP**,
**135 282 480 octets PHP** au total, bundle compris. Les mtimes PHP anciennes
sont préservées et la sonde relève **zéro réécriture PHP** dans chacun des essais.
Les pics RSS ne suivent pas une baisse monotone du garde.

### Modules coûteux

Les cinq premiers modules ont été choisis par leur coût d'optimisation dans le
diagnostic initial. Le module au garde maximal et le gros module Unicode
complètent la sélection. Les temps ci-dessous sont les médianes des builds
ordinaires. Les noms abrégés des projections correspondent aux identifiants
complets conservés dans `results.json`.

| Module | Passages max. | PHP, octets | Optimisation à 11, ms | À 100, ms | À 10 000, ms |
| --- | ---: | ---: | ---: | ---: | ---: |
| `Inter.Cli.Logic.Registry` | 6 | 2 746 128 | 606,446 | 603,949 | 614,397 |
| `Inter.Api.Registry` | 4 | 1 762 416 | 596,820 | 595,869 | 584,222 |
| Projection `SearchArticles` | 5 | 947 482 | 351,440 | 317,769 | 333,833 |
| Projection `GetArticle` | 5 | 789 670 | 330,837 | 304,456 | 300,519 |
| Projection `ListNewsRelatedArticles` | 5 | 650 292 | 223,964 | 219,658 | 218,537 |
| Projection `GetArticleQuote` | 11 | 541 485 | 184,120 | 169,585 | 176,309 |
| `Data.CodePoint.Unicode.Internal` | 4 | 619 549 | 122,535 | 121,078 | 120,264 |

Toutes ces tailles et empreintes PHP sont identiques aux trois gardes. Les
variations temporelles changent de sens selon le module ; les parcours mesurés
confirment que le garde n'a retiré aucun passage coûteux.

## Benchmark : compilation, taille et runtime

Chaque build réussi de la série comparative est suivi d'une exécution PHP
fraîche. PHP **8.5.4**, Xdebug désactivé, OPcache CLI activé, protection de mise
à jour à zéro, JIT **1255**, tampon **128 Mio**. Le stockage de fichiers OPcache
est recréé avant chaque exécution. Le même vendor verrouillé et son lien vers
l'output sont vérifiés ; aucun Composer install/update n'entre dans la mesure.

| Garde | Build médian, s | Optimisation médiane, s | PHP, octets | Runtime médian, ms | Plage runtime, ms |
| ---: | ---: | ---: | ---: | ---: | --- |
| 11 | 3,362 | 1,205 | 7 373 140 | 69,847 | 68,051–70,092 |
| 100 | 3,171 | 1,128 | 7 373 140 | 68,263 | 67,969–71,017 |
| 10 000 | 3,182 | 1,139 | 7 373 140 | 68,188 | 67,803–70,803 |

Les **307 PHP générés** sont identiques. Le manifeste de comparaison couvre
**615 fichiers** du dossier output, CoreFn et métadonnées compris. Le contrôle
du driver retrouve aussi les **350 artefacts PHP/Composer** identiques à son
workspace source. Les **14 valeurs attendues** passent dans les neuf exécutions
comparatives, ainsi qu'au seuil 8 et dans le contrôle final du runner.

Ces temps runtime sont les sommes des mesures internes des quatorze benchmarks,
en millisecondes. Ils ne mesurent pas le démarrage du processus PHP. Les plages
se recouvrent et le code exécuté est strictement identique ; aucune accélération
runtime n'est attribuée au garde.

## Validation et portée du résultat

- `npm run build` : zéro avertissement et zéro erreur.
- `node --test tests/codegen/rewrite-limit.mjs` : **4 contrôles réussis**, couvrant
  validation, précédence, clés persistantes et épuisement réel du garde.
- 18 builds comparatifs réussis, 4 diagnostics complets, contrôles des seuils
  et exécutions PHP avec oracle ; preuves brutes vérifiées par `summarize.py`.
- Contrats et dernière case B4 mis à jour ; défaut de production **10 000** retenu.

La référence b8x du protocole est régénérée avec le CLI figé. Un contrôle annexe
de l'output PHP préexistant du workspace trouve 1 144 différences sur 2 685 PHP
communs ; la provenance de ce précédent output n'établit pas son équivalence
au build courant. Il ne sert donc pas de référence pour comparer les gardes.
Les CoreFn, FFI et Composer capturés restent, eux, identiques à leurs sources.

Les seuils 11 et 8 décrivent ces graphes chargés précis. La campagne ne fournit
aucun compromis de qualité/taille justifiant de réduire la marge de convergence
du défaut commun. Le travail d'optimisation doit être réduit par des changements
de passes mesurés, plutôt que par l'abaissement de ce garde.

## Reproduction

Scripts conservés dans ce dossier :

- `prepare.py` : capturer les entrées existantes et le CLI autonome ;
- `freeze-diagnostic.mjs`, `rewrite-probe.mjs`, `analyze.py` : compter et comparer
  les passages/chunks dans un bundle d'audit ;
- `measure.py` : contrôler limites valides/épuisées, chronos, sorties et runtime ;
- `summarize.py` : vérifier les preuves et produire les [résultats compacts](results.json).

```sh
npm run build
python3 -B audit/2026-10-05/rewrite-tradeoff/prepare.py --artifacts /artefacts/nouveau
node audit/2026-10-05/rewrite-tradeoff/freeze-diagnostic.mjs /artefacts/nouveau/diagnostic.mjs
python3 -B audit/2026-10-05/rewrite-tradeoff/measure.py \
  --artifacts /artefacts/nouveau --project b8x --label diagnostic --limits 10000 --diagnostic
python3 -B audit/2026-10-05/rewrite-tradeoff/measure.py \
  --artifacts /artefacts/nouveau --project b8x --label measured \
  --limits 10000,11,100 --repetitions 3
```

Faire de même pour `--project bench`, avec `--runtime` dans la série mesurée.
Les diagnostics minimaux utilisent `--label diagnostic-min` et les limites 11/8.
Les contrôles de seuil emploient `--label boundary --allow-limit-failure`, avec
`--limits 1,10` pour b8x et `--limits 1,7,8 --runtime` pour les benchmarks.
Le contrôle final du runner utilise `--project bench --label reference-check
--limits 10000 --runtime`. Après ces étapes, `summarize.py /artefacts/nouveau
/chemin/results.json` vérifie les relevés, les sources et les dépendances.
Les gardes à comparer doivent être adaptés si un nouveau snapshot demande plus
de passages. Les preuves locales de cette campagne sont dans :

`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b4-rewrite-20261005`.
