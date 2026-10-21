# Extraction de Main.Outputs

Le 20 octobre 2026. La finalisation des sorties quitte `Main.purs`.

## Résultat

- `Main.purs` : **219 → 183 lignes**.
- Nouveau `Main/Outputs.purs` : **96 lignes**.
- Contenu déplacé : écriture de `runtime.php`, bundles et `main.mod.php`,
  `bundle.php`, Composer et nettoyage des sorties.
- `Main` ne conserve que l’orchestration, le chargement, la préparation
  cache/FFI et la construction des modules.

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**.

Prochaine cible : extraire les options CLI et/ou la préparation cache/FFI du
reste de `Main`.
