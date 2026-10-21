# Extraction de Main.Options

Le 20 octobre 2026. Le parsing des options CLI et des budgets quitte
`Main.purs`.

## Résultat

- `Main.purs` : **183 → 180 lignes**.
- Nouveau `Main/Options.purs` : **52 lignes**.
- Contenu déplacé : `parseCLIArgs`, budgets Purmeta/RewriteLimit, flags
  `verbose`, `bundle-only`, `profile-purmeta`, `no-cache`, émission et
  répertoire de sortie.
- Gain de lignes faible, mais la configuration n’est plus mélangée à
  l’orchestration.

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**.

Reste dans `Main` : préparation des captures FFI, des callbacks et du cache.
