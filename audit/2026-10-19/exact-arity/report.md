# R1 — Premier corps natif à arité exacte

Le 19 octobre 2026. **Intégré et mesuré sur le TAST des benchmarks.** Les
fonctions natives à signature scalaire monomorphe reçoivent un corps privé à
arité exacte ; l'enveloppe publique de currying reste inchangée. Les appels
générés qui fournissent exactement l'arité runtime ciblent ce corps, les autres
gardent l'enveloppe.

Les deux campagnes appariées donnent Fib **1,544–1,527 → 1,122–1,120 µs** et
Ackermann **49,377–48,523 → 46,080–44,942 µs**. La table ci-dessous reprend la
seconde campagne. Le total de la suite y baisse de **68,575 à 67,575 ms**, mais
cette variation n'est pas attribuée à R1 : RBTree ne contient qu'un `max`
inutilisé qui change, et les autres tests varient davantage que le gain direct
attendu.

## Périmètre prouvé

Le générateur émet un corps exact uniquement lorsque toutes les conditions
suivantes sont réunies :

- déclaration `PhpNativeFunction` ou `PhpPrivateFunction` ;
- tous les arguments et le retour ont un type PHP scalaire (`int`, `float`,
  `string` ou `bool`) ;
- le nombre d'arguments émis est exactement l'arité runtime de la table du
  module ;
- le nom interne `...__phpurs_exact` n'entre en collision avec aucune fonction
  du module ;
- la déclaration n'est pas un `PhpCompactLoop`.

Les formes polymorphes, les ADT, les callbacks, les arguments par référence et
les boucles compactes restent sur le chemin actuel. Les wrappers FFI de
`GenNativeForeign.purs` ne sont pas encore concernés.

## Génération

Pour une déclaration éligible, `Printer.purs` émet :

1. `name__phpurs_exact(params requis) { retours directs }` ;
2. l'enveloppe publique `name(...) : T|\Closure`, qui conserve `func_num_args`,
   `phpurs_curry_fallback`, la surapplication et le `$GLOBALS` public ;
3. les appels saturés vers `name__phpurs_exact`, et seulement eux.

Le corps exact conserve le premier paramètre typé et laisse les suivants non
typés, comme l'enveloppe actuelle. Cela évite d'ajouter des contrôles PHP qui
n'existaient pas sur les appels directs. Le retour est précis (`: T`) lorsque le
corps ne contient aucune closure imbriquée. Sinon, il garde `T|\Closure` : une
closure peut être retournée par un alias que l'annotation ne décrit pas, cas
observé avec les thunks d'effet synthétiques.

`printExpr` conserve son ancienne signature et utilise un ensemble exact vide ;
`printPhpFile` calcule les corps exacts à partir des déclarations et de la table
d'arités. Les fonctions locales ordinaires, les boucles compactes et les
traitements FFI ne sont pas modifiés.

## Sorties et intégrité

Comparaison de deux exécutables figés sur les mêmes **306 CoreFn** :

| Mesure | Contrôle | Exact |
| --- | ---: | ---: |
| Fichiers PHP | 307 | 307 |
| Corps exacts | 0 | 47 |
| Modules modifiés | — | 22 |
| Octets PHP | 7 423 151 | 7 430 597 |
| Fib (médiane) | 1,527 µs | 1,120 µs |
| Ackermann (médiane) | 48,523 µs | 44,942 µs |
| Total (médiane) | 68,575 ms | 67,575 ms |

Les **47 corps exacts** sont répartis dans 22 modules, principalement
`Data.Int`, `Data.String.*`, `Data.Number.Approximate`, `Data.Time.Duration`,
`Test.Fib`, `Test.Ackermann`, `Test.RBTree` et `Test.StateMonad`. Chaque module
modifié conserve toutes ses fonctions publiques et toutes ses affectations
`$GLOBALS` ; les 307 fichiers passent `php -l`.

Le seul changement de `Test.RBTree` concerne `max`, qui n'est appelé nulle part
dans le trajet mesuré. Le gain observé sur RBTree dans les deux campagnes
(−1,96 % et −2,06 %) n'est donc **pas** attribué à R1. De même, la baisse du
total n'est pas présentée comme un gain causal : Church et Lazy varient de
plusieurs dizaines de microsecondes dans ces processus.

## Validation

- `npm run build` : aucun warning.
- `npm run test:codegen` : **96/96** contrôles, dont le nouveau
  `exact-arity.mjs`.
- La nouvelle fixture `ExactArity.purs` passe : appel saturé, application
  partielle réutilisée, fonction retournant une fonction, valeur de fonction et
  closure imbriquée.
- Onze fixtures ciblées passent : `ExactArity`, `EffFn`,
  `FunctionFFIBoundary`, `PartialBindings`, `CompactClosureLoops`,
  `RecursiveDictionaryInitialization`, `UnaryCallableCaptures`,
  `NativeArrayCallbacks`, `NativeFoldCallbacks`, `ImmediateStateFusion` et
  `ImmediateThunkFusion`.
- Les huit processus ABBA valident les **14 valeurs** à chaque exécution.
- Le test `exact-arity.mjs` couvre en plus : collisions de suffixe, références
  exclues, argument `null` sur le wrapper, débordement `Int` et nom du contrôle
  de retour.

## Limites et suites

- Le filtre est volontairement scalaire et monomorphe ; les fonctions
  polymorphes et les ADT ne sont pas encore éligibles.
- Les wrappers FFI ne sont pas étendus. C'est la dernière case de R1.
- Les appels exacts suppriment la frame de l'enveloppe publique. Un
  `debug_backtrace` peut donc observer une profondeur différente, et un
  `TypeError` de retour peut nommer `...__phpurs_exact`. Le test de pile de
  `partial-bindings.mjs` reste vert parce que ses fonctions ont des arguments
  fonctionnels et sont exclues, mais ce n'est pas une preuve générale.
- Aucun gain global n'est revendiqué. La prochaine micro-étape recommandée est
  l'extension aux wrappers FFI, avec un contrat explicite pour les contrôles
  d'arguments/retour et les callbacks.

## Reproduction

Construire le contrôle depuis le HEAD, sauvegarder son exécutable, restaurer les
modifications, construire l'exécutable exact, puis lancer :

```sh
python3 audit/2026-10-19/exact-arity/measure.py \
  --control /chemin/control.mjs \
  --exact /chemin/exact.mjs \
  --artifacts /chemin/nouveaux-artefacts
```

Le script recopie les CoreFn, supprime les PHP et caches, régénère les deux
sorties, compare les 307 fichiers, exécute l'ordre ABBA et valide les 14
valeurs. Les résultats complets sont dans [`results.json`](results.json).

Artefacts de la campagne :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-r1-audit-20261019`.
Révision de départ : PHPurs `3d11ebc3445031282bc76e596b5dead973dd1538`.
