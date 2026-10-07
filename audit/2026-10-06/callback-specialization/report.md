# R14 — Spécialisation intégrée des callbacks de folds TAST

Le 6 octobre 2026. **ListOps isolé : 146–148 → 65,8–66,2 µs**, soit environ
**2,23× plus rapide / −55,18 %**. `Phpurs.CallbackSpecialization` spécialise les folds de liste
dont le corps est prouvé dans le TAST, pour un callback natif arithmétique connu.
Les mesures ci-dessous portent sur du PHP **régénéré par le compilateur** avec
la passe active ou désactivée. Le [diagnostic du 5 octobre](../../2026-10-05/traversal-callbacks/report.md)
reste la mesure de prototypes sur copies PHP.

## Transformation et preuves

La passe vient après `StateFusion`, avant `EnumRegions` et l'analyse TCO. Elle
reconnaît sans nom de benchmark un fold polymorphe :

- deux constructeurs, l'un vide et l'autre avec un élément et une queue récursive ;
- trois paramètres runtime : callback, accumulateur, liste ;
- retour de l'accumulateur dans le cas vide ;
- appel `f acc head`, suivi de la récursion avec le même `f` et la queue ;
- échec de correspondance dans le dernier cas.

La signature déclarée et l'annotation native effective sont vérifiées séparément.
Leurs variables de type peuvent être alpha-renommées par PBO, mais doivent garder
les mêmes relations. Une signature fonctionnelle aplatie ne prouve pas à elle
seule l'arité runtime : la chaîne de lambdas doit être disponible, et l'arité
enregistrée par l'émetteur doit permettre l'appel saturé. Les annotations
spécialisées ajoutant un contrôle scalaire et les signatures effectives absentes
ou imbriquées sont refusées.

Le consommateur doit appeler le fold avec ses trois arguments, un callback global
prouvé et un accumulateur littéral `Int`. Les callbacks locaux admissibles sont
des fonctions natives fermées à deux paramètres `Int`, retournant leur addition,
soustraction ou multiplication. Pour le FFI, `NativeCallbacks` accepte seulement
les enveloppes `Data.Semiring.intAdd` et `intMul`, avec signature binaire `Int`
et SHA-256 exact du PHP capturé :

```text
e3a03820f8461b5485f4515d91b6c0f17d092013e8c7cccc2ee8e5448f39b706
```

Ce contrat porte sur les octets utilisés par l'émission et la clé de cache,
quelle que soit leur provenance. Une source différente, absente ou une signature
incompatible ne fournit aucune preuve. La collecte est refaite avant les lookups
du cache ; aucun état de preuve mutable ne survit entre builds.

Une copie privée par paire fold/callback remplace l'application curried du local
par l'appel saturé à l'enveloppe native existante. Le callback reste aussi dans
les arguments transmis : leur évaluation et l'ordre du parcours sont conservés.
Le seed littéral, puis chaque retour contrôlé `Int`, garantissent que le contrôle
du premier argument ne peut pas échouer avant la lecture de l'élément suivant.
**Le contrôle du retour n'est pas supprimé** : un débordement conserve son
`TypeError`, au lieu de laisser passer le flottant produit par un `+` PHP brut.

La copie conserve la signature générique du fold. Son chemin de constructeur
invalide rappelle le fold original, préservant le message et l'emplacement de son
échec généré. Les fonctions publiques et leurs applications partielles restent
disponibles. Les parcours FFI d'Array nécessitent encore un contrat explicite du
corps et du non-échappement des callbacks ; ils ne sont pas sélectionnés ici.

## Bornes et régressions

- Preuves : 1 024 nœuds ; consommateurs : 8 192 ; profondeur : 96 ; largeur : 64.
- Scan : 32 768 nœuds ; au plus 32 copies par invocation, avec noms frais insensibles
  à la casse PHP ; modules limités à 256 groupes, 64 bindings par groupe et 128
  déclarations de types.
- Réutilisation de la copie par paire, parcours des consommateurs imbriqués,
  rescans stables des formes sélectionnées ; le callback direct empêche la copie
  de matcher à nouveau le fold générique.
- **53 refus** couvrent notamment closures inconnues, arité trompeuse, effets dans
  une étape curried, captures, seeds dynamiques, métadonnées de champs incohérentes,
  récursion mutuelle, annotations natives, budgets et types très profonds.
- PHP généré : valeurs vides/positives/négatives, entrée réutilisée, évaluation
  unique de la liste, arithmétique aux limites de `PHP_INT`, éléments FFI mal typés,
  erreur de constructeur, deux étapes curried observables, applications partielles
  conservées et identité des exceptions sentinelles.

Le test exécutable `NativeFoldCallbacks` utilise un autre type (`Chain`) et d'autres
noms (`reduce`, `Cell`, `End`). Il vérifie addition, multiplication, parcours long,
callbacks inconnus et frontières `Foreign.typeOf`/`tagOf`. Sa sortie PHP doit
effectivement contenir les deux copies privées ; le cas à seed dynamique conserve
le fold générique.

## Mesures intégrées

| Mesure | Témoin, deux processus | Passe intégrée, deux processus |
| --- | ---: | ---: |
| ListOps isolé, médiane de 11 lots (µs/action) | 148,358 / 146,016 | 66,154 / 65,785 |
| ListOps dans la suite (µs/action) | 145,899 / 145,266 | 66,076 / 66,389 |
| Total de la suite (ms) | 66,779 / 66,977 | 68,383 / 66,674 |

La médiane des deux médianes isolées passe de **147,187 à 65,970 µs**. Dans la
suite, le gain direct ListOps vaut **79,350 µs**, soit **0,119 %** du total témoin.
La variation de RBTree, dont le PHP est identique, dépasse ce gain : les totaux
ci-dessus ne démontrent pas de gain global. State, ArrayOps et Primes conservent
eux aussi leurs fichiers PHP.

La régénération des **306 modules** donne **307 fichiers PHP**, avec une seule
différence entre variantes : `Test.ListOps/index.php`. Le témoin retrouve les
307 fichiers du compilateur précédent, State intégré compris. Les définitions
publiques précédant `sumEvens` et l'entrée Effect `act` restent identiques.
La taille totale passe de **7 374 518 à 7 376 033 octets** (+1 515).

Comptage sur copies instrumentées séparées, pour une action ListOps :

| Observation | Témoin | Passe intégrée |
| --- | ---: | ---: |
| Entrées dans l'enveloppe native `intAdd` | 900 | 450 |
| Closures partielles du callback | 450 | 0 |
| Additions exécutées | 450 | 450 |
| Visites du fold | 450 | 450 |
| Constructeurs `Cons` | 1 350 | 1 350 |
| Constructeurs `Nil` | 2 | 2 |

## Validation finale et cache

`npm run build` passe sans avertissement, puis **85 contrôles codegen** passent.
Sept fixtures sont exécutées en PHP et JavaScript : `NativeFoldCallbacks`,
`ImmediateStateFusion`, `ImmediateThunkFusion`, `PartialBindings`,
`CompactClosureLoops`, `FunctionFFIBoundary` et
`RecursiveDictionaryInitialization`.

`bin/php/run -c` recompile normalement le backend et le programme : les **14
valeurs attendues** passent. Ses 307 PHP actifs sont identiques à la variante
intégrée mesurée. La suite comparative a également validé les 14 valeurs dans
chacun de ses quatre processus ; le noyau isolé retourne toujours `202950`.

Le script `cache.py` utilise la CLI packagée, une copie du CoreFn et une source
FFI adjacente choisie explicitement :

| Scénario | Hits | Modules retraduits | PHP ListOps |
| --- | ---: | ---: | --- |
| Froid | 0 | 306 | Spécialisé |
| Chaud | 306 | 0 | Spécialisé |
| Consommateur changé, FFI restauré du cache | 99 | 207 | Spécialisé |
| Un octet FFI ajouté, mtime conservé | 13 | 293 | Générique |
| Octets FFI originaux rétablis | 306 | 0 | Spécialisé |
| Sans cache | — | 306 | Spécialisé |

Chaque état redonne les **307 PHP** du témoin attendu et exécute ListOps avec la
valeur `202950`. Le changement FFI est une ligne vide : sa sémantique est identique,
mais ses octets ne satisfont plus le contrat exact. Le test vérifie donc le retour
conservateur au parcours générique et la restauration ultérieure de la preuve.

Le contrôle final couvre **587 fichiers d'entrée** inchangés, **326 modules JS**
capturés, les deux exécutables figés, les sorties des variantes, le vendor et les
307 sorties PHP actives. Résultats complets : [`results.json`](results.json).

Révisions de départ : PHPurs `6745b189806df940ec8183c0f183ad6a8783c631`, PBO
`157a544f0a469c7b0137a3fca66e626d717db493`, benchmarks
`be2b32d564a72fbafc429373697872b0a66e3c4c`. La campagne finale utilise :

```text
/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-callback-specialization-20261006-validated
```

## Reproduction

Depuis PHPurs, construire le compilateur avec `npm run build`, puis choisir un
dossier d'artefacts neuf. Les benchmarks PHP doivent déjà avoir leurs CoreFn TAST
et leur vendor verrouillé.

```sh
python3 audit/2026-10-06/callback-specialization/prepare.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-06/callback-specialization/measure.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-06/callback-specialization/count.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-06/callback-specialization/cache.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-06/callback-specialization/validate.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-06/callback-specialization/summarize.py --artifacts "$ARTIFACTS"
```

`prepare.py --control <ancien-output>` permet en plus de vérifier que le témoin
régénéré est identique au PHP issu du compilateur précédent. `freeze.mjs` capture
une seule fois les modules JavaScript ; seule l'implémentation d'`optimize` dans
`Phpurs.CallbackSpecialization` est neutralisée pour le témoin. Les comptages
utilisent d'autres copies, avec JIT/OPcache désactivés.

La mesure isolée et la suite utilisent chacune quatre processus frais en ordre
**ABBA**, OPcache CLI actif, cache de fichiers vide, JIT `1255`, buffer 128 Mio,
protection de mise à jour à zéro et Xdebug désactivé. Le driver isolé fait trois
échauffements, calibre un lot d'au moins 20 ms, puis conserve onze échantillons
par processus. Les médianes/IQR/échantillons bruts figurent dans `results.json`.

La validation normale `bin/php/run -c` repackage l'exécutable ignoré
`bin/phpurs.js` via Spago. Ses empreintes avant/après sont enregistrées séparément
de celles des exécutables figés utilisés pour mesurer.
