# Chemin de build unique

Le 20 octobre 2026. Le rebuild du benchmark utilisait deux chemins différents :

- `npm run build` : `spago build` puis `tools/bundle.mjs` ; identité d’outillage et
  cache de modules actifs ;
- `bin/php/run -c` : `spago build` puis `spago bundle --module Main`, qui appelle
  `Main.main` sans identité de toolchain et désactive donc le cache.

Le runner benchmark a été aligné sur `node tools/bundle.mjs` après le `spago
build` épinglé au compilateur hôte. Il utilise désormais le même exécutable que
le chemin npm, avec l’identité et le cache normaux.

## Vérification

`./bin/php/run -c` passe : rebuild, 14 benchmarks validés, total **64,01 ms**.
Aucun fichier source PHPurs n’a été modifié.

La modification est dans le worktree benchmark `altbak.pub-phpurs`, pas dans
`phpurs/phpurs`.
