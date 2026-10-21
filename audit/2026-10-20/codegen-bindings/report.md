# Extraction de CodeGen.Bindings

Le 20 octobre 2026. Quatrième extraction de la Phase 2 : l’analyse des groupes
de bindings quitte `CodeGen.purs`.

## Résultat

- `CodeGen.purs` : **834 → 763 lignes**.
- Nouveau `CodeGen/Bindings.purs` : **94 lignes**.
- Contenu déplacé : `AnalyzedBindingGroup`, `analyzeBindings`,
  `isSafeRecursiveInit`, `extractUncurriedAbs`.
- `translateBindingGroup` et `translateBinding` restent dans le noyau, car ils
  dépendent directement de `translateExpr`.

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**.

Prochaine cible : séparer l’analyse d’ownership de la réécriture dans
`ArrayRefs.purs`, ou extraire une première partie du printer.
