# R8 — Captures compactes intégrées aux boucles éligibles

**La première transformation générique est intégrée dans PHPurs et le PHP actif a été régénéré.** LazyEvaluation prend **49,540–49,886 ms dans les quatorze benchmarks**, contre **104,907–105,704 ms** lorsque cette seule passe est désactivée. Le total mesuré est **327,679–331,761 ms**, contre **372,677–399,379 ms** pour le témoin. `altbak.pub-phpurs/bin/php/run -c` passe également, avec un relevé ponctuel de **327,37 ms**.

Les quatorze résultats sont exacts, R9 est conservé, et le PHP actif correspond par empreinte à la variante intégrée de la comparaison. Aucun nom de benchmark n'intervient dans le critère du compilateur. Il ne s'agit plus de la réécriture diagnostique du PHP de buildThunks.

## Périmètre et preuve

`CodeGen` appelle `CompactLoops.optimize` seulement pour une fonction native récursive d'un groupe à une fonction, traduite en TCO. La passe reçoit son label de boucle et la signature encore disponible dans l'expression typée originale. L'analyse suit ensuite les usages dans l'AST PHP structuré ; elle ne déduit pas les types des noms de variables.

Les conditions initiales sont conservatrices :

- résultat fonctionnel unaire, argument et résultat scalaires (`Int`, `Number`, `String`, `Boolean` ou `Unit`) ;
- un seul paramètre fonctionnel ayant exactement ce type, identifié par la signature TAST ;
- au plus 4 096 nœuds au niveau du corps de la fonction et un saut vers le label TCO connu ;
- au moins une fonction unaire capturant l'accumulateur par valeur, dont les usages satisfont l'analyse.

La fermeture des alias part du paramètre fonctionnel et suit les affectations locales, les slots de résultats et les captures. Elle converge en au plus 64 passages, sinon la fonction reste inchangée. L'analyse est volontairement conservative et peut suivre aussi des dépendances scalaires pour refuser un cas incertain.

Les valeurs suivies peuvent être copiées localement, capturées dans un corps admissible, transmises au tour suivant ou retournées directement. Le corps compact peut invoquer la fonction capturée, avec le résultat scalaire garanti par la signature. Il ne peut pas exposer cette fonction à un autre appel, la mettre dans un agrégat, la retourner lui-même, inspecter son identité ou ses champs, ni la réaffecter. Les captures par référence, closures imbriquées, mutations et PHP brut non reconnu sont exclus. Un passage intermédiaire par FFI/Foreign ou un record provoque donc le repli sur le code existant.

La signature TAST est transportée explicitement jusqu'à cette décision. Cette première passe utilise les types monomorphes de la fonction et les usages structurés ; elle n'ajoute pas de nouvelle spécialisation polymorphe `TypeApp` dans PBO. Les cas qui ne correspondent pas à cette signature restent inchangés.

## Représentation et retour public

Deux nœuds explicites de `PhpAst` portent la décision : `PhpCompactFunction` et `PhpCompactLoop`. Le printer émet un objet invocable avec des champs privés pour les captures, rechargées dans des locaux à chaque invocation. Le corps de curry/surapplication est partagé avec les closures ordinaires ; les captures par valeur conservent ainsi leur comportement lors des appels répétés.

Seuls les modules contenant une boucle sélectionnée déclarent l'interface interne `Phpurs_InternalCallable`. Avant le retour public, le wrapper convertit une valeur portant ce marqueur en `Closure` via `Closure::fromCallable`. Les autres valeurs, notamment une Closure d'entrée retournée à profondeur zéro, restent inchangées. Les annotations PHP existantes des paramètres et le protocole d'application partielle/surapplication sont conservés.

La conversion de racine simple est retenue : le précédent essai ne montrait aucun avantage temporel à éviter les 1 000 conversions lors de la surapplication. Les corps non sélectionnés restent imprimés comme auparavant. La passe R9 ignore les nouvelles déclarations de boucles compactes.

## Validation

- **CompactClosureLoops** construit une chaîne `Int -> Int` en capturant le numéro de chaque itération, puis vérifie les racines réutilisées, les appels répétés, les applications partielles/surapplications et les routes Closure/Foreign/record. Dans le même module, `growObserved` transmet chaque fonction intermédiaire à une FFI exigeant Closure : son PHP reste non compact. Le runner vérifie ces deux formes générées avant de les exécuter.
- Cette fixture et onze régressions passent **en PHP et JavaScript** : FunctionFFIBoundary, TerminalInlining, UnaryCallableCaptures, BranchBindingSlots, RecursiveDictionaryInitialization, DeepCase, ShadowedRename, ShadowedTCOLet, TCOCase, TCOMutRec, CaseMultipleExpressions. Voir [fixtures.json](fixtures.json).
- `tests/codegen/compact-loops.mjs` exécute le PHP AST original et optimisé : contrat public Closure, captures mutables, curry et exception identique. Il vérifie **21 cas de refus**, dont alias vers FFI, agrégats, mutation, observation d'identité, fuite depuis le callback, captures par référence, types inconnus et plafonds de l'analyse.
- `tail-inline.mjs`, `branch-slots.mjs` et `unary-callables.mjs` passent.
- La validation étendue de LazyEvaluation est rejouée sur le code généré : profondeurs 0/1/2/17/1000, graines −3/0/11, tags de la vraie FFI Foreign, paramètres Closure, état mutable partagé et exceptions. Voir [validate-lazy.json](validate-lazy.json).
- `bin/php/run -c` reconstruit le backend avec le compilateur hôte standard, puis le programme avec le fork TAST ; les quatorze sorties sont correctes. Voir [full-build.log](full-build.log).

Le runner habituel exige encore `phpurs-math`, absent de ce checkout. Les douze fixtures utilisent un projet isolé avec les paquets frères nécessaires ; cette validation ne représente pas toute la suite historique. L'introspection PHP des closures et les usages FFI arbitraires hors du contrat testé ne sont pas couverts par ces vérifications.

## Comptages et mesure contrôlée

`prepare-comparison.mjs` régénère les deux programmes à partir du même tcorefn : un bundle temporaire remplace uniquement `CompactLoops.optimize` par l'identité pour le témoin. Les chemins des sources CoreFn sont rendus absolus pour retrouver les mêmes FFI dans les copies. **Seul Test.LazyEvaluation diffère parmi les 299 modules**, et le SHA-256 de RBTree est celui de R9. Voir [generated-state.json](generated-state.json).

Les compteurs sont exécutés séparément, sans JIT. Une action construit et invoque **1 000 000 nœuds**, avec **1 000 appels à buildThunks** dans les deux versions. La version intégrée ajoute exactement **1 000 conversions en Closure**, aussi bien pour l'action actuelle que pour les racines demandées séparément. Le résultat reste `1000000`. Voir [counts.json](counts.json).

Les chronos utilisent `Bench.purs` : trois échauffements, minimum de dix passages, somme des quatorze minima. PHP 8.5.4, OPcache CLI, JIT 1255, buffer 128 MiB ; Xdebug et compteurs absents. Pas de réutilisation d'un cache de fichiers OPcache. Les processus sont successifs, puis l'ordre est inversé. Voir [measurements.json](measurements.json).

| Ordre | Variante | Lazy isolé | Lazy dans les 14 | RBTree dans les 14 | Total |
| --- | --- | ---: | ---: | ---: | ---: |
| 1 | Passe désactivée | 106,638 ms | 105,704 ms | 274,590 ms | 399,379 ms |
| 1 | Intégrée | 47,784 ms | 49,540 ms | 263,348 ms | 331,761 ms |
| 2 | Intégrée | 49,067 ms | 49,886 ms | 259,028 ms | 327,679 ms |
| 2 | Passe désactivée | 103,288 ms | 104,907 ms | 249,219 ms | 372,677 ms |

Le gain stable sur LazyEvaluation est d'environ **55–56 ms dans la suite complète**, soit environ **53 % de temps en moins**. Le total dépend aussi des variations de RBTree, dont le PHP est pourtant identique ; le témoin du premier ordre est particulièrement lent. On conserve les totaux observés sans attribuer tout leur écart à la passe.

Le [README historique d'altbak.pub](../../../../../altbak.pub-phpurs/README.md) donne **91,955 ms pour LazyEvaluation et 381,61 ms au total**. Le code intégré se situe environ **46 % sous cette référence Lazy** et **13–14 % sous son total**. Le relevé ponctuel du script actif, 327,37 ms, n'est pas utilisé seul pour calculer un pourcentage.

Le module LazyEvaluation grossit de **313 octets** (13 817 → 14 130). Le pic PHP alloué reste à **42 MiB** pour la suite complète. Ce changement accélère le stockage des captures et leur destruction ; il ne supprime pas les nœuds ni les invocations de la charge.

## Reproduction

Depuis la racine PHPurs, après `bin/php/run -c` dans altbak.pub-phpurs :

```sh
node tests/codegen/compact-loops.mjs
node tests/codegen/tail-inline.mjs
node tests/codegen/branch-slots.mjs
node tests/codegen/unary-callables.mjs
python3 audit/2026-09-08/r8-integrated/run-fixtures.py
node audit/2026-09-08/r8-integrated/prepare-comparison.mjs
python3 audit/2026-09-08/r8-integrated/count.py
python3 audit/2026-09-08/r8-integrated/measure.py
```

`build-host.sh` permet aussi de reconstruire uniquement l'outil PHPurs avec le compilateur standard épinglé. Les projets et bundles temporaires restent dans `raw/`. Les modifications sont dans le checkout PHPurs existant, sans commit ni merge effectué par cette étape ; PBO et les paquets FFI partagés ne sont pas modifiés.
