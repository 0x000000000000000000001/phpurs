# Extraction de CodeGen.Tco

Le 20 octobre 2026. Deuxième extraction de la Phase 2 : les helpers de boucle
TCO quittent `CodeGen.purs`.

## Résultat

- `CodeGen.purs` : **902 → 855 lignes**.
- Nouveau `CodeGen/Tco.purs` : **68 lignes**.
- Contenu déplacé : `containsNestedLoop`, `findTailTarget`, `translateTailJump`,
  `wrapLoopBody`.
- `CodeGen` importe explicitement les quatre helpers ; aucun type public n’est
  déplacé.

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**, aucun changement de contenu.

Aucun changement d’algorithme. Prochaine extraction proposée :
`CodeGen/Effects.purs` (`isEffectNode`, `executeIfOpaque`).
