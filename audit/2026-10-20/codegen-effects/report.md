# Extraction de CodeGen.Effects

Le 20 octobre 2026. Troisième extraction de la Phase 2 : la classification et
l’exécution différée des effets quittent `CodeGen.purs`.

## Résultat

- `CodeGen.purs` : **855 → 834 lignes**.
- Nouveau `CodeGen/Effects.purs` : **33 lignes**.
- Contenu déplacé : `isEffectNode`, `executeIfOpaque`.
- `CodeGen` les importe explicitement ; aucun comportement ni type public ne
  change.

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**.

Prochaine cible recommandée : les helpers de bindings (`analyzeBindings`,
`isSafeRecursiveInit`, `extractUncurriedAbs`) avant de toucher au noyau
récursif.
