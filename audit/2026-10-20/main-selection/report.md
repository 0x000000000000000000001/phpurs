# Extraction de Main.Selection

Le 20 octobre 2026. La sélection des modules quitte la partie orchestration de
`Main.purs`.

## Résultat

- `Main.purs` : **242 → 219 lignes**.
- Nouveau `Main/Selection.purs` : **60 lignes**.
- Contenu déplacé : graphe de modules, fermeture des imports, détection des
  mains exportant `main`, sélection des modules.
- `Main` convertit les erreurs pures en erreurs `Aff` et conserve le même
  comportement de build.

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**.

Le reste de `Main.purs` concerne encore l’orchestration, la préparation du
cache/FFI et la finalisation des sorties.
