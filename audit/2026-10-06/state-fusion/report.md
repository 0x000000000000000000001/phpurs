# R14 — Intégration des chaînes State immédiatement exécutées

Le 6 octobre 2026. **Passe `StateFusion` intégrée au générateur**, après
`ThunkFusion` et avant `EnumRegions`. Les comparaisons utilisent du PHP
entièrement régénéré, avec la passe activée ou désactivée. Le prototype du
[5 octobre](../../2026-10-05/state-chains/report.md) reste un relevé distinct.

## Preuve et transformation

La passe reconnaît structurellement un constructeur récursif de State dont la
signature aplatie est `(Int, Int) -> { valeur :: Unit, état :: Int }` et dont
l'arité runtime est **un**. Les noms de champs, fonctions et modules ne sont pas
des critères : la fixture utilise `Store`, `assemble`, `result` et `store`.

La preuve exige :

- un cas zéro retournant l'état reçu et la valeur `Data.Unit.unit` canonique ;
- une décrémentation de profondeur par un, avec une transition avant chaque
  appel récursif ;
- un helper de modification polymorphe du même module, dont le corps appelle
  son callback exactement une fois et construit les deux champs attendus ;
- si le helper lit un getter, la preuve de son corps et de sa signature,
  avec les deux champs égaux à l'état reçu ;
- un callback fermé `Int -> Int`, limité à `+`, `-` ou `*` par un littéral ;
- un consommateur complètement appliqué et immédiatement projeté sur le champ
  d'état ; profondeur et état initial sont des littéraux typés ou des locaux
  stricts déjà évalués dans la portée courante.

Les signatures polymorphes **et les annotations natives effectives** des helpers
sont vérifiées. Un helper monomorphe susceptible d'introduire un contrôle PHP est
refusé. Les métadonnées de type profondément imbriquées sont refusées sans
parcours récursif non borné.

Le worker scalaire réutilise la génération TCO de `ThunkFusion.makeWorker`.
Chaque transition conserve son opération arithmétique ; aucune multiplication
par la profondeur ni préévaluation du résultat n'est introduite. Les signatures
internes évitent d'ajouter un contrôle `Int` au résultat flottant possible après
débordement PHP.

Une profondeur dynamique passe par un garde privé. Il conserve **le contrôle
PHP `int` du paramètre de profondeur** du constructeur original. La branche
négative reprend ce constructeur et la projection d'origine. Son application
imbriquée n'est pas candidate à la passe : trois rescans restent idempotents.

Les States conservés, projections du champ valeur, records retournés, callbacks
inconnus, helpers FFI et captures non prouvées conservent leur chemin original.
Les fonctions privées ne reçoivent pas de binding `$GLOBALS`.

### Budgets

1 024 nœuds par constructeur/helper, 8 192 par caller, profondeur 96, largeur 64,
32 768 visites de scan par module, 32 workers et au plus un garde par worker.
Les modules dépassant 256 groupes ou 64 bindings par groupe sont refusés.

## Mesures après régénération

Quatre processus frais en ordre **témoin / intégré / intégré / témoin** pour
chaque mode. PHP **8.5.4**, JIT **1255**, buffer **128 Mio**, OPcache CLI actif,
cache fichier désactivé et Xdebug absent. Le noyau isolé emploie le même driver
que le prototype : trois échauffements, calibration à au moins 20 ms puis
**11 lots**, `hrtime(true)` et vérification de chaque résultat.

| Mesure | Passe désactivée | Passe intégrée |
| --- | ---: | ---: |
| Médianes des processus isolés | 478,250 / 468,827 µs | 2,611 / 2,682 µs |
| State dans la suite complète | 480,043 / 478,822 µs | 2,589 / 2,569 µs |
| Total des 14 tests | 67,699 / 67,646 ms | 66,374 / 66,689 ms |

Le noyau isolé passe de **473,538 à 2,647 µs** sur la médiane des deux
processus, soit **99,44 % de temps en moins**, environ **179 fois plus rapide**.
La contribution directement mesurée dans la suite est **0,477 ms**. Le PHP
généré conserve la boucle TCO générique ; le prototype manuel à 1,15–1,17 µs
reste distinct.

Le total baisse davantage que cette contribution directe : RBTree, dont les
octets sont identiques, varie de 55,461–55,532 à 54,607–54,852 ms. Une série
préliminaire sur les **mêmes 307 fichiers PHP** avait donné State isolé
490–495 → 2,72–2,74 µs, mais des totaux recouvrants de 69,894–71,714 ms contre
70,049–71,056 ms. Elle est conservée dans le dossier d'artefacts voisin
`phpurs-state-fusion-20261006-integrated`. La dernière compilation ajoute un
refus borné des métadonnées de type profondes ; elle reproduit le même PHP.
**Aucun gain global supplémentaire n'est attribué à State** sur cette machine
partagée.

Taille PHP totale : **7 373 140 → 7 374 518 octets**, soit **+1 378 octets**.
Pic mémoire PHP isolé inchangé à **4 Mio**. Les échantillons, versions,
empreintes, valeurs des 14 tests et paramètres effectifs sont conservés dans
[`results.json`](results.json). Ces temps mesurent le runtime ; aucune réduction
du temps de compilation ni du démarrage n'est revendiquée.

## Travail réellement exécuté

[`count.py`](count.py) instrumente des copies séparées, sans OPcache/JIT. Pour une
action State complète, le résultat reste **1 200** :

| Compteur | Passe désactivée | Passe intégrée |
| --- | ---: | ---: |
| Closures du callback | 1 200 | 0 |
| Closures d'application partielle | 1 200 | 0 |
| Closures de continuation | 1 200 | 0 |
| Closures du cas zéro | 20 | 0 |
| Records | 2 420 | 0 |
| Additions des transitions | 1 200 | 1 200 |
| Additions de l'accumulateur extérieur | 20 | 20 |

## Validation

- Build sans warning et **84 contrôles codegen réussis**.
- `state-fusion.mjs` : **60 refus** ciblés, dont frontières et corps inconnus,
  portées, helpers monomorphes, types profonds et budgets ; noms privés frais et
  renommage complet du module, des fonctions et des champs.
- **70 comparaisons numériques PHP**, dont zéro, profondeur 2 048,
  `PHP_INT_MAX - 1`, soustractions et multiplications. Comparaison exacte du type
  et de la valeur sérialisée ; le raccourci arithmétique après débordement reste
  explicitement exclu.
- Ordre et unicité des entrées issues de FFI, identité des exceptions, captures
  mutables de callbacks publics, exécution répétée des States, records frais,
  type PHP `Closure` et frontière `Foreign.typeOf`/`tagOf`.
- Repli négatif instrumenté : arguments conservés, exception sentinelle
  identique, projection du record retourné sans invocation du champ valeur.
- Six fixtures passent en **PHP et JavaScript** : `ImmediateStateFusion`,
  `ImmediateThunkFusion`, `PartialBindings`, `CompactClosureLoops`,
  `FunctionFFIBoundary`, `RecursiveDictionaryInitialization`.
- `bin/php/run -c` réussit avec les **14 valeurs attendues**.

## Reproduction et intégrité

Le témoin pré-changement a été régénéré et comparé aux 307 fichiers PHP actifs.
[`freeze.mjs`](freeze.mjs) fige ensuite les mêmes modules JavaScript du
compilateur dans deux exécutables : seule `StateFusion.optimize` devient une
identité dans le témoin. [`prepare.py`](prepare.py) régénère les 306 modules
CoreFn dans deux sorties neuves avec les mêmes sources FFI et Composer.

**Un seul fichier PHP change sur 307 : `Test.StateMonad/index.php`.** Son préfixe
public jusqu'à `runManyTimes`, ainsi que l'action Effect, sont identiques. Le
témoin avec passe désactivée reproduit exactement le contrôle pré-changement.
Le contrôle final compare aussi le PHP actif aux fichiers réellement mesurés.
Il vérifie **585 fichiers d'entrée**, **324 modules JavaScript du compilateur**,
les deux exécutables figés, les 307 PHP actifs et le vendor. Le runner peut
repackager `bin/phpurs.js` via Spago ; ses empreintes avant/après sont conservées
séparément des exécutables utilisés pour la mesure.

Depuis le checkout PHPurs, avec un dossier d'artefacts neuf :

```sh
npm run build
npm run test:codegen
python3 audit/2026-10-06/state-fusion/prepare.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-06/state-fusion/count.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-06/state-fusion/measure.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-06/state-fusion/summarize.py --artifacts "$ARTIFACTS"
```

Le résumé exige que les sorties actives aient été régénérées avec le nouveau
backend. `--control` peut désigner une copie PHP pré-changement lors de la
préparation. Les scripts ne modifient pas les fichiers PHP mesurés ; les
compteurs sont ajoutés à des copies distinctes.

Révisions de départ : PHPurs `6745b189806df940ec8183c0f183ad6a8783c631`, PBO
`157a544f0a469c7b0137a3fca66e626d717db493`, benchmarks
`be2b32d564a72fbafc429373697872b0a66e3c4c`. Implémentation livrée dans le checkout,
sans commit de cette étape ; les empreintes des exécutables mesurés sont dans
[`results.json`](results.json).

Artefacts complets :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-state-fusion-20261006-final`.
