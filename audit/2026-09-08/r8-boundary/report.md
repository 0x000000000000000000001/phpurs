# R8 — Contrat des fonctions aux frontières FFI

La fixture `tests/passing/FunctionFFIBoundary` passe en **PHP 8.5.4 et en JavaScript** avec le compilateur actuel. Elle conserve une même fonction capturant un entier, l'appelle directement, la place dans un record, la transmet à la vraie bibliothèque `Foreign`, la récupère depuis `Foreign` et la fait conserver/invoquer plusieurs fois par une FFI. Les résultats, captures et tags sont vérifiés. Deux fonctions capturent des graines différentes pour détecter un mélange des captures.

La déclaration PHP de `invokeClosure` exige une `Closure`. `invokeRecord` applique aussi cette contrainte au champ `callback`. Les opérations de conservation et rappel sont correctement déclarées comme effets ; les identités FFI `opaque` et `roundTripForeign` sont pures.

PBO repliait initialement les appels directs vers de l'arithmétique et recopiait la lambda dans plusieurs usages. `opaque` reçoit maintenant la fonction elle-même : le PHP garde une allocation liée au local `$f_3_3`, des appels `$f_3_3(...)`, et ce même local dans le record. Ce passage à une FFI opaque fait explicitement partie de la fixture ; elle ne sert pas à prouver qu'une fonction ne s'échappe jamais.

Le backend, PBO, les paquets FFI partagés et les benchmarks actifs ne sont pas modifiés. Cette étape n'intègre pas encore de gain runtime.

## Contre-exemples exécutés

`probe.php` utilise les wrappers FFI réellement générés et une closure récupérée après l'exécution de la fixture. Il compare cette closure à un objet invocable capturant la même valeur, puis à une conversion ponctuelle par `Closure::fromCallable`. Ce sont des objets de diagnostic ; aucune réécriture du générateur n'est activée.

| Route | Closure générée | Objet compact | Conversion directe seule | Conversion directe et champ |
| --- | --- | --- | --- | --- |
| Appels directs et champ | Corrects | Corrects | Corrects | Corrects |
| Tags de la fonction via Foreign | function/Function | object/Object | function/Function | function/Function |
| Paramètre PHP Closure | Correct | TypeError | Correct | Correct |
| Callback du record exigé Closure | Correct | TypeError | TypeError | Correct |
| Record revenu de Foreign puis callback typé | Correct | TypeError | TypeError | Correct |
| Fonction conservée puis rappelée deux fois | Correct | Correct | Correct | Correct |

Les conversions des deux routes rétablissent exactement les observations de cette sonde. Cela ne prouve pas une conversion générique de tous les records, collections, alias, fonctions récursives ou contrats FFI. Les valeurs retournées, erreurs et tags sont conservés dans [observations.json](observations.json).

## Informations TAST effectivement présentes

`inspect-tast.py` lit les tcorefn réels, développe les références de `typeTable` et vérifie les éléments suivants. Le [relevé](tast-evidence.json) contient les lignes source, types et empreintes des fichiers analysés.

- `opaque` est instancié à `Int -> Int` pour les deux fonctions et au record structurel pour `handler`.
- Les `TypeApp` de `unsafeToForeign` / `unsafeFromForeign` portent explicitement la fonction ou le record avec son champ `callback :: Int -> Int`. Il y a 97 nœuds TypeApp dans la fixture complète.
- Les usages de `f`, de `handler` et de `invokeRecord` possèdent les annotations attendues aux points d'appel. L'ordre des champs du record est conservé dans le relevé.
- Une nuance observée : dans `foreignAnnotations.invokeRecord`, le paramètre écrit avec le synonyme `Handler` est `Any` dans ce fichier, alors que **l'annotation de `invokeRecord` à chaque utilisation et celle de son argument donnent bien le record structurel**. Une analyse fondée uniquement sur la table des déclarations foreign manquerait donc une information disponible aux usages. Les annotations de certains bindings locaux sont également nulles ; leurs usages et instanciations restent typés.

Le parseur PBO lit `ExprTypeApp`, la conversion produit `Syn.TypeApp`, et PHPurs traite actuellement les enveloppes `Typed` / `TypeApp` en traduisant leur expression sous-jacente. Le PHP produit ne comporte plus les appels à `unsafeToForeign` et `unsafeFromForeign` : les coercitions de cette fixture sont effacées à l'exécution. Une future décision de représentation doit donc exploiter les types et les usages avant cette étape, ou transmettre explicitement sa décision à l'AST PHP.

Le TAST décrit la fonction et le contenu du record. L'exigence PHP précise `Closure`, ainsi que les tests `instanceof Closure` de Foreign, proviennent du contrat FFI ; ils ne se déduisent pas du seul type PureScript `Func`.

## Prochain essai borné

Le TAST de LazyEvaluation donne les fonctions `Unit -> Int` et la source montre une chaîne capturée dans `buildThunks`, forcée par `runManyTimes`. Le PHP actuel transforme la construction récursive en boucle TCO : les nœuds intermédiaires circulent dans cette boucle. `buildThunks` est cependant exportée ; son résultat constitue aussi une frontière publique.

La prochaine expérience proposée est une copie isolée de cette construction : garder les captures internes dans des objets invocables et convertir uniquement la racine retournée par `buildThunks` en Closure, lorsque nécessaire. Sur la charge actuelle, cela viserait au plus **1 000 conversions à la sortie des chaînes**, au lieu d'une conversion pour chacun des **1 000 000 thunks**. Ce compte reste à vérifier par instrumentation ; il ne constitue pas un gain mesuré.

Petites étapes :

1. Tester cette conversion de sortie sur la copie PHP, en conservant le comportement public à profondeur zéro, les captures, les appels répétés et le protocole de curry/surapplication. Compter objets, conversions et invocations.
2. Mesurer LazyEvaluation et les quatorze benchmarks avec R9 conservé. Comparer aux derniers contrôles intégrés de 355–359 ms au total, ainsi qu'au README historique (LazyEvaluation 91,955 ms ; total 381,61 ms).
3. Si le gain subsiste, définir une preuve générique du trajet interne et de sa frontière de retour avant de l'intégrer. Les fonctions passant par des FFI ou agrégats dont les usages restent inconnus conservent la représentation actuelle. Le contre-exemple des records reste dans les régressions.

Cette expérience précisera le plan de conversion ; elle n'est pas encore effectuée à cette étape. Aucune extension aux records arbitraires n'est nécessaire pour la lancer.

## Reproduction

Depuis la racine PHPurs, avec le backend courant déjà construit :

```sh
python3 audit/2026-09-08/r8-boundary/run-fixture.py
php -d opcache.enable_cli=0 audit/2026-09-08/r8-boundary/probe.php
python3 audit/2026-09-08/r8-boundary/inspect-tast.py
```

Le runner isolé sélectionne le fork TAST du worktree altbak.pub et les paquets frères nécessaires, dont `phpurs-foreign`. Il vérifie la présence de `typeTable`/`dataDecls` et exécute les mêmes assertions en PHP et JavaScript. [fixtures.json](fixtures.json) conserve les résultats ; le journal de compilation est adjacent. Les sources de la fixture sont dans `tests/passing`, utilisables aussi par le runner habituel lorsque ses dépendances seront toutes présentes.
