# R14 — Spécialisation intégrée des parcours FFI d’Array

Le 6 octobre 2026. **ArrayOps isolé : 98–100 → 13,7–13,8 µs**, soit
**×7,22 / −86,14 %** avec les deux spécialisations. Les variantes sont régénérées
par le compilateur à partir des mêmes entrées, en gardant les passes State et
List précédemment intégrées. Le [diagnostic initial](../../2026-10-05/traversal-callbacks/report.md)
portait sur des modifications de copies PHP ; cette campagne valide le générateur.

## Contrats intégrés

`ForeignTraversals` vérifie le module, la signature polymorphe, la convention
d'appel et le SHA-256 du fichier FFI capturé pour l'émission et la clé de cache :

| Parcours | Contrat | SHA-256 |
| --- | --- | --- |
| `Data.Foldable.foldlArray` | Arity native 3 ; longueur calculée une fois ; indices croissants ; `f(acc)` puis `f1(xs[i])` ; accumulateur renvoyé ; aucune mutation ou rétention du callback. | `ea67972f7d24df92cc8eb6c316316c43ee133c114b41c39269b2ebe49f53e19f` |
| `Data.Array.filterImpl` | `Fn2` brut ; `foreach` par valeur, ordre d'insertion ; un appel unaire par valeur ; nouveau tableau dense construit par append ; aucune mutation ou rétention du callback. | `0233eeb307fce0499fb051f835f21a09c78cdd1447522fbe2b8067e53e7efe64` |

La collecte est refaite avant chaque lookup du cache. Des octets différents ou une
signature incompatible désactivent le contrat concerné, même si le module FFI a
été restauré dans un build précédent.

`ArrayCallbacks` sélectionne les consommateurs dans le TAST, après la passe List
et avant `EnumRegions`. Les workers PHP sont ajoutés après les passes d'inlining
et d'ownership ; leurs arités privées sont enregistrées avant impression. Le
`foreach` est un template fixe contenant uniquement une expression scalaire
fermée produite par le printer, sans extension partielle des traversées de l'AST.

- **Fold** : callback natif binaire `Int` prouvé et seed littéral typé `Int`.
  Appel saturé de l'enveloppe existante, conservant son contrôle de retour et son
  `TypeError` à débordement. Le seed et les retours contrôlés rendent infaillible
  le contrôle du premier argument avant la lecture suivante. Les non-arrays et
  tableaux PHP non denses reviennent au FFI original : `Countable`, `ArrayAccess`
  et diagnostics d'indices creux conservent leur comportement.
- **Filtre** : lambda unaire immédiate sans capture ni annotation fonctionnelle.
  Booléens, comparaisons `Int`, opérations logiques et modulo du paramètre par un
  littéral non nul sont prouvés. Les éléments PHP `int` utilisent l'expression
  spécialisée ; les autres appellent la closure originale. Les entrées non-array,
  notamment les itérateurs, reviennent au FFI original. Le tableau filtré demeure
  matérialisé, dense et ordonné avant le fold suivant.
- Les expressions callback et collection restent évaluées une fois. Une copie
  privée est partagée par callback natif ou prédicat normalisé. Les parcours
  publics, callbacks inconnus, applications partielles et captures restent
  disponibles avec leur protocole d'origine.

Bornes : prédicat 128 nœuds, consommateur 8 192, profondeur 96, largeur 64, scan
32 768 nœuds, 32 copies, 256 groupes, 64 bindings par groupe, 128 déclarations.
Les noms sont frais en tenant compte de la casse PHP ; les workers n'ont pas
d'entrée `$GLOBALS`. Les rescans des formes sélectionnées sont stables.

## Mesures sur le PHP régénéré

Chaque ligne contient les résultats de deux processus frais. L'ordre est
**témoin / fold / filtre / combiné / combiné / filtre / fold / témoin**, séparément
pour le noyau isolé et la suite complète.

| Variante | Médianes isolées (µs/action) | ArrayOps dans la suite (µs/action) | Totaux de la suite (ms) |
| --- | ---: | ---: | ---: |
| Témoin | 98,390 / 100,348 | 98,549 / 99,513 | 69,508 / 68,949 |
| Fold seul | 17,696 / 17,634 | 18,106 / 17,931 | 69,717 / 70,393 |
| Filtre seul | 94,798 / 95,428 | 93,289 / 94,969 | 70,188 / 70,204 |
| Combiné intégré | 13,842 / 13,703 | 13,550 / 13,755 | 69,886 / 69,092 |

Médianes isolées : **99,369 → 17,665 / 95,113 / 13,772 µs**, soit respectivement
**−82,22 % / −4,28 % / −86,14 %**. Le résultat combiné est mesuré directement.
Dans la suite, le gain direct ArrayOps vaut **85,378 µs**, environ **0,123 %** du
total témoin. La variation des autres noyaux, dont le PHP est identique, dépasse
ce gain ; ces totaux ne démontrent pas de gain global.

OPcache CLI activé, JIT `1255`, buffer 128 Mio, cache fichier vide, protection de
mise à jour à zéro, Xdebug désactivé. Le noyau isolé vérifie effectivement JIT et
OPcache : trois échauffements, calibration d'un lot d'au moins 20 ms, puis onze
lots. Chaque invocation vérifie et consomme `202950`. La suite conserve son
protocole normal et valide ses quatorze valeurs dans chacun des huit processus.

## Périmètre des sorties et comptage

Le témoin reproduit les **307 PHP** du compilateur précédent. La combinaison
modifie quatre fichiers : `Test.ArrayOps`, `Test.WorkloadArrayInt`,
`Test.WorkloadArrayNumber` et `Test.WorkloadMap`. Seul ArrayOps appartient aux
quatorze noyaux de la suite publiée. Les définitions FFI publiques, ListOps et
State gardent leurs empreintes ; l'entrée Effect d'ArrayOps est identique.

Le workload Number contient déjà un prédicat constant après PBO. Sa copie est
donc sélectionnée ; les éléments non-Int continuent d'appeler sa closure originale.
Le résultat de ce workload est comparé au témoin, sans attribuer de gain numérique
ou de changement de représentation à cette passe.

Taille PHP totale : **7 376 033 → 7 380 790 octets**, soit +4 757. Les comptages
utilisent des copies instrumentées distinctes, avec JIT/OPcache désactivés :

| Par action ArrayOps | Témoin | Fold seul | Filtre seul | Combiné |
| --- | ---: | ---: | ---: | ---: |
| Entrées dans l'enveloppe `intAdd` | 900 | 450 | 900 | 450 |
| Closures partielles de l'addition | 450 | 0 | 450 | 0 |
| Additions / visites du fold | 450 | 450 | 450 | 450 |
| Appels au prédicat | 900 | 900 | 0 | 0 |
| Tests modulo / visites du filtre | 900 | 900 | 900 | 900 |
| Valeurs de range produites | 900 | 900 | 900 | 900 |
| Éléments du tableau filtré | 450 | 450 | 450 | 450 |

## Régressions et cache

Le build passe sans avertissement et les **86 contrôles codegen** passent. Le
nouveau fichier `array-callbacks.mjs` couvre **34 refus**, contrats FFI modifiés,
arités/conventions, seeds dynamiques, captures, contrôles scalaires, modulo nul,
budgets, noms frais, partage des copies, rescans, évaluation unique des entrées,
débordements, éléments FFI mal typés, tableaux creux, ordre des callbacks et
identité des exceptions. Les sorties générées sont exécutées avec la passe
active et désactivée.

Le cache est testé sur les 306 modules réels :

| Scénario | Hits | Modules retraduits | Configuration retrouvée |
| --- | ---: | ---: | --- |
| Froid | 0 | 306 | Combiné |
| Chaud | 306 | 0 | Combiné |
| Consommateur changé, contrats FFI restaurés | 94 | 212 | Combiné |
| Octets Foldable changés | 74 | 232 | Filtre seul |
| Octets des deux FFI changés | 93 | 213 | Témoin |
| Octets Array seuls changés | 93 | 213 | Fold seul |
| Sources originales rétablies | 306 | 0 | Combiné |
| Sans cache | — | 306 | Combiné |

Les mutations ajoutent une ligne vide à mtime conservé : la sémantique FFI est
identique, mais le contrat exact doit être retiré. Chaque état redonne les 307
PHP de sa configuration figée et exécute ArrayOps avec `202950`.

Le workload auxiliaire `WorkloadArrayNumber`, annoncé « Purust-only » dans ses
sources, échoue déjà dans le témoin PHP au FFI indisponible `Data.Number.floor`.
La validation compare la classe et le message de ce `TypeError` entre variantes
et exécute directement les fonctions de somme/filtre modifiées avant cet appel.
Ce cas n'est pas présenté comme une exécution réussie du workload complet.

Les quatre fixtures `NativeArrayCallbacks`, `NativeFoldCallbacks`,
`PartialBindings` et `FunctionFFIBoundary` passent en PHP et JavaScript.
`NativeArrayCallbacks` est aussi compilée/exécutée en bundle, avec présence
effective des trois copies privées et refus du prédicat capturant un paramètre.
Les workloads ArrayInt et Map retournent respectivement **35 406 173 408** et
**400 040 000** dans les quatre variantes ; le contrôle Number décrit ci-dessus
retrouve la même erreur et les mêmes résultats directs de somme/filtre.

`bin/php/run -c` recompile le backend et le programme : les **14 valeurs attendues**
passent, et les 307 fichiers actifs sont identiques à la variante combinée mesurée.
Le contrôle final couvre **589 fichiers d'entrée** et **328 modules JS** inchangés,
les quatre exécutables figés, le vendor et les sorties PHP. Le repackaging Spago
de l'exécutable ignoré `bin/phpurs.js` a ses empreintes avant/après distinctes de
celles des compilateurs mesurés. Tous les résultats et preuves d'intégrité sont
dans [`results.json`](results.json).

## Reproduction

Après `npm run build`, avec le workspace PHP des benchmarks et son vendor
verrouillé, choisir un dossier neuf :

```sh
python3 audit/2026-10-06/array-callbacks/prepare.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-06/array-callbacks/measure.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-06/array-callbacks/count.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-06/array-callbacks/cache.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-06/array-callbacks/validate.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-06/array-callbacks/summarize.py --artifacts "$ARTIFACTS"
```

`prepare.py --control <ancien-output>` vérifie aussi les sorties du compilateur
précédent. `freeze.mjs` capture les modules JS une seule fois ; seule la fonction
de sélection d'`ArrayCallbacks` varie entre les quatre exécutables. Les configurations
partielles retirent un contrat de l'ensemble réellement prouvé, sans en inventer.

Révisions de départ : PHPurs `6745b189806df940ec8183c0f183ad6a8783c631`, PBO
`157a544f0a469c7b0137a3fca66e626d717db493`, benchmarks
`be2b32d564a72fbafc429373697872b0a66e3c4c`. Artefacts :

```text
/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-array-callbacks-20261006-final
```
