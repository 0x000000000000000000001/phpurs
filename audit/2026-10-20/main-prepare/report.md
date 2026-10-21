# Extraction de Main.Prepare

Le 20 octobre 2026. La préparation FFI/cache quitte `Main.purs`.

## Résultat

- `Main.purs` : **180 → 146 lignes**.
- Nouveau `Main/Prepare.purs` : **101 lignes**.
- Contenu déplacé : résolution des racines de paquets, captures FFI, sources
  externes, callbacks natifs, traversals, inputs et préparation du cache.
- `Main` garde l’enchaînement du build et les callbacks PBO.

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**.

`Main` est maintenant principalement un orchestrateur ; la Phase 2 peut être
close après vérification des derniers éléments de découpage.
