# R14 — Potentiel de spécialisation des callbacks de parcours

Le 5 octobre 2026. **Mesures terminées sur des copies du PHP généré.** Le coût
dominant trouvé est l'application partielle répétée de l'addition : un appel
natif saturé ramène **ListOps de 154–156 à 69–70 µs**, et **ArrayOps de 100–103
à 18 µs**, avec les mêmes parcours et contrôles numériques. La spécialisation
du seul prédicat ArrayOps apporte environ **7 %**. Ces prototypes ne constituent
pas une passe intégrée au compilateur.

## Inventaire réel des sites

Les sources étudiées sont `Test.ListOps`, `Test.ArrayOps` et `Test.Primes` du
worktree `altbak.pub-phpurs`. Leurs sorties sont capturées après PBO par
[`capture.mjs`](capture.mjs), pendant une régénération normale. Les **306 modules**
redonnent exactement les **307 fichiers PHP** actifs.

| Site | Ce qui subsiste dans le trajet mesuré |
| --- | --- |
| ListOps, filtre des pairs | Le modulo et le test sont déjà dans la boucle. |
| ListOps, fold de l'addition | `f(acc)(value)` : deux entrées dans l'enveloppe native et une closure partielle par élément. |
| ArrayOps, filtre | FFI `filterImpl` : un appel au prédicat PHP par élément, avec production du tableau filtré. |
| ArrayOps, fold | FFI `foldlArray` : `f(acc)`, puis application au tableau à l'indice courant. |
| Primes, filtre capturant le nombre premier | Le callback a déjà été intégré par PBO : modulo par le nombre premier capturé directement dans la boucle. |

Le comptage de l'action Primes confirme **5 182 évaluations** du prédicat modulo
et **zéro appel** au filtre générique. Ses coûts de construction de listes et de
boucles locales ne sont donc pas un gain de callback restant à attribuer ici.
La charge réelle porte sur les nombres jusqu'à **500**, malgré des commentaires
historiques évoquant 20 000 dans la source.

## Trois substitutions indépendantes

[`prepare.py`](prepare.py) prépare un témoin et trois variantes. Chaque variante
ne modifie qu'un site de `sumEvens` et ajoute son worker dans un seul module PHP.
Les fonctions de parcours publiques, les actions Effect, `Bench.opaque`, les
sources des bibliothèques et les autres modules sont conservés.

### `list-add`

Une copie du fold PHP généré remplace uniquement :

```php
(($callback)($acc))($value)
```

par l'appel à arité exacte du callback connu :

```php
\Data\Semiring\majData_majSemiring_intmajAdd($acc, $value)
```

Le worker garde la structure TCO, les tests de constructeurs, le sens du fold,
les arguments et le protocole d'arité du fold original. Le callback passé au
site est toujours évalué ; il n'est plus appliqué en deux étapes dans la copie.

### `array-add`

Le [worker Array](workers.php) reprend la boucle de la FFI `foldlArray` : longueur
calculée une fois, indices croissants, même accumulateur. Il appelle également
l'addition native saturée. Le range et le filtre précédent, son callback et son
tableau de sortie restent exécutés. Le wrapper du parcours est contourné une
fois par action ; les 450 applications partielles sont évitées dans la boucle.

### `array-filter`

Une copie du `foreach` de `filterImpl` contient directement `($x % 2) === 0`.
Elle produit toujours le tableau dense filtré dans le même ordre ; le fold qui
suit garde son callback curried. L'expression créant le prédicat reste passée à
l'entrée du worker : le prototype mesure principalement les **900 invocations**
éliminées, pas la suppression de cette unique closure d'entrée.

Les deux workers Array sont présents dans chacune des copies Array, mais un
seul site est redirigé selon la variante. Aucune combinaison de ces substitutions
ni fusion `range → filter → fold` n'est comptée comme mesurée.

## Contrat numérique : découverte importante

La FFI brute `intAdd` effectue `$a + $b`, mais son enveloppe PHP générée accepte
un premier paramètre **`int`** et retourne **`int|Closure`**. Le remplacement
direct par une primitive `+` dans le fold ferait disparaître ce contrôle après
chaque addition.

Le contre-exemple `[PHP_INT_MAX, 1]` le vérifie : le fold original lève un
`TypeError` au retour de `Data.Semiring.intAdd`, tandis qu'un fold à `+` brut
retourne un flottant. Les prototypes utilisent donc l'enveloppe native existante.
Les cas de débordement produisent la **même classe et le même message d'erreur**
que le témoin. La sémantique arithmétique existante, y compris ses contrôles
intermédiaires, fait partie du résultat à préserver.

## Temps isolés

PHP **8.5.4**, JIT **1255**, buffer **128 MiB**, OPcache CLI actif, cache fichier
désactivé, Xdebug absent. Chaque processus isolé vérifie l'état effectif du JIT.
[`measure-isolated.php`](measure-isolated.php) exécute la véritable action du
module après chargement des dépendances : trois échauffements, calibration à
au moins **20 ms par lot**, puis **11 lots** avec `hrtime(true)`. Le résultat
**202 950** est vérifié et consommé à chaque invocation.

Ordres : **témoin / list-add / list-add / témoin** pour ListOps ; **témoin /
array-add / array-filter / array-filter / array-add / témoin** pour ArrayOps.

| Variante | Médianes des deux processus | Réduction de la médiane |
| --- | ---: | ---: |
| ListOps témoin | 154,490 / 156,392 µs | — |
| ListOps, addition saturée | 68,764 / 69,636 µs | 55,48 % |
| ArrayOps témoin | 103,313 / 100,499 µs | — |
| ArrayOps, addition saturée | 18,402 / 18,136 µs | 82,07 % |
| ArrayOps, prédicat spécialisé seul | 94,513 / 95,177 µs | 6,93 % |

Les temps couvrent toute l'action, y compris construction des collections,
filtrage et fold. Ils ne sont pas ceux d'une boucle arithmétique substituée à la
charge. Tous les échantillons, paramètres, pics mémoire et empreintes sont
conservés dans [`results.json`](results.json).

## Suite complète

Huit processus frais utilisent le vrai `App/main.mod.php`, dans l'ordre
**témoin / list-add / array-add / array-filter / array-filter / array-add /
list-add / témoin**. Le protocole normal de `Bench.purs` conserve le meilleur
de dix lots calibrés ; l'oracle du driver valide les **14 valeurs** à chaque run.

| Variante | Test affecté dans la suite | Total des 14 tests |
| --- | ---: | ---: |
| Témoin | List 151,902 / 151,478 µs ; Array 100,487 / 99,873 µs | 71,549 / 70,947 ms |
| `list-add` | List 69,126 / 67,272 µs | 71,920 / 70,537 ms |
| `array-add` | Array 18,341 / 17,908 µs | 71,363 / 70,902 ms |
| `array-filter` | Array 95,551 / 94,799 µs | 70,734 / 71,052 ms |

Le gain directement mesuré est **83,49 µs** pour ListOps, **82,06 µs** pour le
fold Array et **5,01 µs** pour son prédicat, soit respectivement **0,117 %**,
**0,115 %** et **0,007 %** du total témoin. Les totaux varient davantage sur cette
machine partagée : leurs écarts ne constituent pas une mesure causale de ces
petits gains. Les gains de variantes séparées ne sont pas additionnés pour
annoncer un résultat combiné.

Taille PHP totale : **7 373 140 octets** pour le témoin, **7 374 414** pour
`list-add`, **7 373 663** pour `array-add`, **7 373 678** pour `array-filter`.
Chacune des variantes modifie un seul fichier parmi les 307.

## Comptage

[`count.py`](count.py) utilise des copies instrumentées distinctes avec
JIT/OPcache désactivés. Pour chaque action ListOps ou ArrayOps :

| Observation | Témoin | Addition saturée | Prédicat Array seul |
| --- | ---: | ---: | ---: |
| Entrées dans l'enveloppe native `intAdd` | 900 | 450 | 900 |
| Closures partielles de `intAdd` | 450 | 0 | 450 |
| Additions réellement exécutées | 450 | 450 | 450 |
| Éléments parcourus par le fold | 450 | 450 | 450 |
| Appels du prédicat Array | 900 | 900 | 0 |
| Tests modulo Array | 900 | 900 | 900 |

ListOps construit toujours **1 350 Cons et deux Nil**. ArrayOps produit toujours
**900 valeurs de range**, effectue **900 visites** du filtre et écrit **450
valeurs** dans son tableau de sortie. Les variantes non pertinentes pour une
action redonnent tous ses comptes témoins. Le comptage Primes est confronté à
un crible Python indépendant pour confirmer les 5 182 tests.

## TAST, frontières et validation

[`inspect.py`](inspect.py) vérifie les sites optimisés réels :

- ListOps appelle `foldl` avec trois arguments, dont le global connu
  `Data.Semiring.intAdd` et l'accumulateur zéro. Dans le fold polymorphe, le local
  callback est appliqué à deux arguments. L'identité connue du callback et son
  **arité runtime native** sont les informations à propager à une future copie.
- ArrayOps appelle `Data.Foldable.foldlArray` avec le même callback. Le corps
  du parcours reste dans la **FFI PHP**, hors du TAST.
- `Data.Array.filterImpl` est un **`UncurriedApp` à deux arguments**. Son
  prédicat est une lambda unaire sans capture, avec modulo par deux et test
  d'égalité à zéro ; son corps n'appelle aucune fonction.
- Primes ne contient plus d'appel au filtre générique dans `sieve` et conserve
  directement l'opérateur modulo dans la boucle.

Une intégration devra reconnaître ces formes indépendamment des noms de
benchmarks, borner la spécialisation et garder des copies privées. Une signature
fonctionnelle aplatie ne prouve pas l'arité native, ni l'absence d'effets ou
d'observation de pile dans un callback. Pour les parcours FFI, il faut aussi un
contrat explicite du corps : ordre des visites, convention d'appel, collections
produites et non-échappement du callback ne sont pas décrits par le type seul.

[`validate.php`](validate.php) réussit pour chacune des quatre variantes :

- **20 cas consommateurs** List/Array, dont entrées négatives et descendantes,
  zéro, 900 et 3 000 ;
- **27 cas de fold**, dont **cinq débordements**, avec comparaison sérialisée
  des valeurs ou de la classe et du message d'erreur ;
- filtres sur tableaux denses et contrôle de l'ordre/renumérotation des clés
  pour un tableau creux ; entrées tableaux/listes conservées sans mutation ;
- callbacks publics inconnus avec effets dans les deux étapes curried,
  applications partielles conservées et réutilisées, exceptions sentinelles
  identiques dans chaque étape ;
- prédicat capturant une variable modifiée entre deux appels, ordre des visites,
  vraie `Closure`, `Foreign.typeOf`/`tagOf` et paramètre PHP typé `Closure` ;
- contre-exemple de saturation arbitraire : appliquer deux arguments à une
  véritable fonction curried PHP retourne sa closure intermédiaire au lieu du
  résultat. La saturation exige une cible native prouvée.

Le filtre FFI brut est uncurried ; les vérifications d'application partielle
portent sur l'API publique `Data.Array.filter`, qui fournit ce contrat.

## Choix de la prochaine intégration

Le potentiel est réel mais représente environ **0,08 ms par fold** dans cette
suite. La [campagne State précédente](../state-chains/report.md) avait mesuré
environ **0,53 ms** directement dans State. Ce sont deux campagnes distinctes,
pas une comparaison appariée de transformations combinées.

La prochaine intégration recommandée dans le backlog est donc **une passe State
générique bornée**, avec ses preuves et régressions. La saturation de callbacks
connus reste une étape distincte ultérieure ; son premier cas purement TAST
pourrait être le fold de liste. Les résultats du diagnostic FFI Array ne valent
pas encore preuve d'une transformation générique du compilateur.

## Reproduction et intégrité

Depuis PHPurs, avec les modules JS du compilateur construits et le workspace
PHP des benchmarks préparé avec son vendor verrouillé, choisir un dossier neuf :

```sh
python3 audit/2026-10-05/traversal-callbacks/prepare.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-05/traversal-callbacks/inspect.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-05/traversal-callbacks/measure.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-05/traversal-callbacks/count.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-05/traversal-callbacks/summarize.py --artifacts "$ARTIFACTS"
```

Le contrôle final vérifie **1 143 fichiers d'entrée**, les **307 fichiers PHP
actifs**, les quatre variantes mesurées et le vendor. Les modifications de la
campagne State précédente sont conservées ; cette étape ajoute son audit et
actualise le backlog.

Révisions au début de la campagne : PHPurs
`90d831579f9f725e09fa709050430a7f0d21eed8`, PBO
`157a544f0a469c7b0137a3fca66e626d717db493`, benchmarks
`be2b32d564a72fbafc429373697872b0a66e3c4c`.
Artefacts complets :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-r14-traversal-callbacks-20261005`.
