# Extraction de CodeGen.Context

Le 20 octobre 2026. Première extraction de la Phase 2 : les types et l’état
partagés de traduction quittent `CodeGen.purs`.

## Résultat

- `CodeGen.purs` : **946 → 902 lignes**.
- Nouveau `CodeGen/Context.purs` : **56 lignes**.
- Contenu déplacé : `TranslationResult`, `TranslatedValues`, `LoopContext`,
  `TranslationContext`, `initialContext`, `valueContext`.
- `CodeGen` réexporte le module via `module Phpurs.CodeGen.Context`, donc
  l’API existante (`initialContext`, types) reste disponible.

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**, aucun fichier ajouté ou modifié.

Aucun changement d’algorithme. Prochaine extraction : `CodeGen/Tco.purs`
(`containsNestedLoop`, `findTailTarget`, `translateTailJump`, `wrapLoopBody`).
