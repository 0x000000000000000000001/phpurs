# B4 — accumulation linéaire des opérandes

Date : 3 octobre 2026. Troisième micro-étape B4.

## Coût identifié avant modification

Le profil de compilation précédent place `Data.CodePoint.Unicode.Internal` au
14e rang des coûts de traduction du corpus b8x, à **47,075 ms**. Ce module
contient notamment un littéral de **3 396 éléments**.

Une instrumentation du JavaScript généré, dans un bundle d'audit séparé,
compte les longueurs des préfixes recopiés par `snoc` et les concaténations de
`translateValues`. Sur une traduction réelle du module :

| Accumulation | Valeurs ajoutées | Éléments de préfixe recopiés | Plus grand préfixe |
| --- | ---: | ---: | ---: |
| `translateValues`, expressions | 4 843 | 6 651 530 | 3 395 |
| `translateValues`, statements | 4 843 passages | 0 | 0 |
| Littéraux records, expressions | 15 447 | 17 298 | 5 |

Le nombre de copies de l'accumulateur d'opérandes suit bien la somme des
préfixes, donc une croissance quadratique avec la largeur. Un profil CPU Node
à intervalle de 250 µs, après échauffement, attribue environ **71,2 ms** sur
quinze traductions aux opérations `copyImpl`/`pushImpl` ayant cet accumulateur
comme appelant. Le profil distingue les appels analogues des records.
Ces échantillons localisent le coût ; leurs durées ne servent pas à mesurer le
gain de la variante.

## Changement intégré

Le seul accumulateur modifié est `translateValues` dans `Phpurs.CodeGen`.
Deux tableaux frais et un compteur local sont construits dans une région `ST`.
Chaque opérande est traduit dans l'ordre, avec le `nextId` rendu par le
précédent, puis ses statements et son expression sont ajoutés aux tableaux.
Les tableaux sont figés après la construction ; aucune référence mutable ne
s'échappe pendant celle-ci. L'ajout des statements utilise une boucle, sans
spread ni `push.apply` dépendant d'une limite d'arguments JavaScript.

L'accumulation coûte désormais O(nombre d'opérandes + nombre de statements),
en plus du coût propre de traduction des sous-expressions. Le contrat de
`translateValues`, les positions valeur/effet et le fil des IDs restent ceux
utilisés par ses appelants.

## Régressions et échelle synthétique

`npm run build` réussit sans avertissement ni erreur. Les **78 contrôles** de
`npm run test:codegen` passent. `operand-accumulator.mjs` ajoute :

- une borne déterministe sur les copies de préfixe, pour 4 096 opérandes purs
  puis 4 096 opérandes produisant chacun un statement ;
- tableaux vides, singleton et **100 000 éléments**, avec ordre, compteur et
  indépendance des résultats vérifiés ;
- appels curryfiés/uncurried et tableaux de closures échappantes : ordre des
  statements, IDs distincts, captures et appels répétés vérifiés en PHP.

La sonde de la même borne échoue sur le bundle témoin figé et passe après :
**8 386 560 → 0** éléments de préfixe recopiés pour les valeurs pures et
**16 773 120 → 0** avec statements. Les allocations nécessaires aux éléments et
la capacité interne des tableaux JS ne sont pas des préfixes `slice`/`concat`.

Les chronos suivants sont pris dans des processus séparés, sans instrumentation
de copies, avec trois échauffements puis quinze passages par largeur. Médianes
de `translateExpr`, analyse TCO et vérifications hors horloge :

| Valeurs pures | Témoin, ms | Linéaire, ms |
| ---: | ---: | ---: |
| 512 | 0,258 | 0,126 |
| 1 024 | 0,578 | 0,216 |
| 2 048 | 1,903 | 0,352 |
| 4 096 | 6,784 | 0,465 |
| 8 192 | 23,320 | 0,932 |

Avec 8 192 bindings produisant un statement, la médiane passe de **108,147 à
66,574 ms** ; la traduction de chaque binding reste une part importante de ce
travail. Les entrées synthétiques décrivent l'échelle de l'accumulateur, pas le
gain global de compilation.

## Modules réels isolés

Les entrées sont quatre `BackendModule` typés figés provenant du corpus b8x,
restaurés avec leurs constructeurs. Les bundles avant/après exposent les mêmes
fonctions de traduction et leurs propres constructeurs. Ce sont des fixtures
de mesure ; chaque build complet est lancé avec `--no-cache`.

Trois paires de processus, dans les ordres avant/après, après/avant,
avant/après ; quatre traductions préalables puis quinze mesures par module.
Chargement des fixtures et calcul de l'empreinte de l'AST PHP sont hors horloge.
Chaque empreinte produite est identique entre les variantes et les répétitions.

| Module | Médiane des trois médianes témoin, ms | Linéaire, ms |
| --- | ---: | ---: |
| `Data.CodePoint.Unicode.Internal` | 36,946 | 31,962 |
| `Inter.Cli.Logic.Registry` | 150,963 | 148,896 |
| `Inter.Api.Registry` | 86,013 | 83,967 |
| `Core.Feat.Review.Message.Query.SearchArticles.Projection.Projection` | 89,632 | 91,874 |

Le module ciblé gagne **13,49 %** sur cette synthèse. Chacune des trois paires
améliore sa médiane (**10,8 à 16,8 %**). La variation des autres modules est
modeste et de signes différents selon les paires ; elle ne démontre pas de gain
général sur tous les appels courts. Aucun gain runtime de l'application n'est
attribué à ce changement de construction du compilateur.

## Compilation complète b8x

Le script [verify.py](verify.py) prépare le corpus immuable et des sorties
existantes vérifiées, puis exécute les CLIs figés dans l'ordre **avant, après,
après, avant**. Les quatre essais comparent ainsi des fichiers préexistants,
avec la même date ancienne pour les mtimes PHP.

Options : `--main Inter.Api.Main --bundle --no-cache --profile-build`,
`GOPURS_JOBS=1`, budget PBO par défaut, Node v24.8.0. La sonde I/O vérifie les
lectures CoreFn, publications `.purmeta`, empreintes et écritures PHP réelles.
Les temps de processus excluent les vérifications d'empreintes du script.

| Essai | Processus, s | Traduction totale, s | Traduction du module ciblé, ms | Pic RSS, KiB |
| --- | ---: | ---: | ---: | ---: |
| 1 — témoin | 48,318 | 6,672 | 42,635 | 3 779 440 |
| 2 — linéaire | 49,535 | 6,824 | 34,477 | 3 742 432 |
| 3 — linéaire | 52,860 | 7,137 | 34,825 | 3 723 536 |
| 4 — témoin | 51,792 | 7,087 | 43,962 | 3 800 688 |

Les quatre builds terminent les **2 684 modules** et conservent **5 372 fichiers
identiques**, dont les **2 686 PHP** avec leurs mtimes anciennes et **zéro
réécriture PHP**. Le corpus d'entrée reste inchangé. Le gain local se retrouve
dans les deux builds complets de la variante, autour de 34,5–34,8 ms contre
42,6–44,0 ms dans les contrôles.

Le temps total et la traduction globale ne montrent pas d'accélération nette :
leurs variations dépassent le gain local de quelques millisecondes. La machine
est partagée et ces quatre observations ne suffisent pas à attribuer une petite
variation globale à la passe. Les résultats détaillés figurent dans
[results.json](results.json).

## Suite runtime des quatorze benchmarks

Le workspace `pure` est copié dans les artefacts, à sa profondeur relative
d'origine, avec le lien vers les bibliothèques PHPurs. Les deux CLIs régénèrent
les mêmes CoreFn via `--main App --no-cache`. Les empreintes des CoreFn, FFI
adjacentes et entrées du driver sont vérifiées avant/après ; les dépendances
Composer copiées et leur lien vers l'output sont contrôlés.

Les **350 artefacts** sont identiques entre les deux régénérations, dont les
**307 PHP générés**. Le driver PHP et son validateur `core` confirment les
**14 valeurs attendues** dans chaque exécution. Le cache de fichiers OPcache est
recréé avant chaque processus ; les réglages usuels JIT `1255`, tampon 128 MiB
et Xdebug désactivé sont conservés. Les sommes observées de 71,487 et 71,958 ms
sont des contrôles de résultats sur un PHP identique, sans gain runtime annoncé.

Un premier déplacement de cette copie à une autre profondeur avait cassé les
chemins FFI relatifs et fait échouer le témoin dans `Data.Show`. Le layout a été
rectifié, puis les deux régénérations et exécutions ont réussi. Le journal de
cet essai de préparation est conservé sous `benchmark-relocation-failed.log`.

## Provenance et reproduction

Base PHPurs : `8d8295456101a5eea908161bd8d02112275ff703`.
Base PBO : `c9386b4d572503bb7b6d1ce9547ec30dcded2920`.
SHA-256 CLI témoin : `6d3c9172d475bcc3f943ec3b779eb64f3bef840947ba5cc86634b71858c056a7`.
SHA-256 CLI linéaire : `358bd327a0ea245be51c137e7914af4707e321d2f16dedb741ec8ef44bcfaaa9`.
Les empreintes des bibliothèques de sonde et fixtures sont conservées dans les
résultats et artefacts. Le manifeste du corpus de 2 839 fichiers reste
`9e159394a364c95af49aa1d66cdcc7d3c66ec410acbaf70b19f96bfda98e7372`.

Scripts fournis :

- `freeze.mjs` : figer une bibliothèque de sonde à partir du `output` du backend
  reconstruit ; `--copies` instrumente la version témoin de l'accumulateur ;
- `module-probe.mjs` : mesures isolées, comptage des copies ou profil CPU ;
- `scaling-probe.mjs` : largeurs synthétiques, timings ou vérification de borne ;
- `verify.py` : comparaison complète ABBA avec contrôles I/O et empreintes.

```sh
node audit/2026-10-03/linear-accumulator/freeze.mjs /artefacts/library.mjs
node audit/2026-10-03/linear-accumulator/module-probe.mjs \
  /artefacts/library.mjs /artefacts/states.json /artefacts/modules.json
node audit/2026-10-03/linear-accumulator/scaling-probe.mjs \
  /artefacts/library.mjs /artefacts/scaling.json check
python3 audit/2026-10-03/linear-accumulator/verify.py \
  --snapshot /chemin/vers/snapshot \
  --reference-output /chemin/vers/sorties-verifiees/output \
  --before /artefacts/before.mjs --after bin/phpurs.js \
  --artifacts /artefacts/validation-vide
```

Artefacts locaux :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b4-accumulator-kyls4vxg`.
Ils contiennent les fixtures sérialisées et leurs imports, bundles figés,
échantillons, profil CPU, sondes de copies, journaux et arbre b8x validé.
