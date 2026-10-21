# Nœud AST PhpNull

Le 20 octobre 2026. Les valeurs `null` générées ne sont plus des chaînes
`PhpRaw`.

## Résultat

- Nouveau constructeur `PhpNull` dans `PhpExpr`.
- `PhpNull` est imprimé `null`, traverse sans enfant et peut être mappé.
- `CodeGen`, la fusion de thunks, les boucles compactes, `NullableConstructors`,
  `CopyCleanup`, `TailInline` et `ArrayRefs/Rebuild` utilisent `PhpNull`.
- Les passes continuent d’accepter `PhpRaw "null"` pour les anciens AST de test
  et les constructions externes.

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**.

Prochaine étape : traiter de la même façon les autres `PhpRaw` récurrents
(noms de fonctions/opérateurs), sans changer les chaînes PHP opaques.
