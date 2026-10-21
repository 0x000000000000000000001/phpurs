# Cartographie de CodeGen

Le 20 octobre 2026. Inventaire statique de `src/Phpurs/CodeGen.purs` (946
lignes, 31 définitions de premier niveau), sans modification.

## Groupes identifiés

| Groupe | Lignes | Taille approx. | Dépendances |
| --- | ---: | ---: | --- |
| Types et contexte | 55–97 | 43 | `PhpExpr`, `TcoExpr`, `Map` |
| Helpers TCO | 160–195, 318–327 | 46 | types de traduction, `TcoAnalysis`, `PhpExpr` |
| Fusion de records | 197–300 | 104 | `TranslationContext`, `translateValue` |
| Traducteur d’expressions | 302–690 | 389 | noyau récursif, `TcoExpr`, `PhpExpr` |
| Bindings et pipeline | 691–921 | 231 | traducteur, `TcoAnalysis`, `PhpDecl` |
| Helpers Effect | 926–946 | 21 | `TcoExpr`, `PhpExpr` |
| Orchestration | 723–819 | 97 | passes locales, `ModuleState`, `PhpFile` |

## Ordre proposé

1. **`CodeGen/Types.purs`** : `TranslationResult`, `TranslatedValues`,
   `LoopContext`, `TranslationContext`, `initialContext`, `valueContext`.
   C’est le prérequis sans dépendance montante.
2. **`CodeGen/Tco.purs`** : `containsNestedLoop`, `findTailTarget`,
   `translateTailJump`, `wrapLoopBody`. Extraction mécanique après les types.
3. **`CodeGen/Effects.purs`** : `isEffectNode`, `executeIfOpaque`.
4. **`CodeGen/Bindings.purs`** : `analyzeBindings`, `isSafeRecursiveInit`,
   `extractUncurriedAbs`, `translateBindingGroup` et `translateBinding`.
5. **`CodeGen/Pipeline.purs`** : `translate`, `translateWithCallbacks`,
   `translateWithContracts` et l’assemblage des passes.

## Ce qu’il ne faut pas faire tout de suite

- Extraire la grande fonction récursive `translateSyntax` avant d’avoir stabilisé
  les interfaces : elle traverse presque tous les groupes.
- Extraire `tryFusedUpdate` seul : il appelle `translateValue`, ce qui créerait
  un cycle. Il faudra soit le garder dans le noyau, soit injecter une fonction
  de traduction explicite.
- Déplacer plusieurs groupes dans la même étape : chaque extraction doit être
  compilée et testée isolément.

## Critères de sortie

- `CodeGen.purs` ne garde que le noyau récursif et l’orchestration restante.
- Aucun cycle d’imports ; les types partagés sont dans `CodeGen/Types.purs`.
- `npm run build` et `npm run test:codegen` (**101/101**) restent verts.
- Aucun changement de comportement ou de PHP généré pour une entrée inchangée.
