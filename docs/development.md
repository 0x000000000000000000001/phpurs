# Développement

## Build

Le chemin canonique est :

```sh
npm run build
```

Il exécute `spago build` puis `tools/bundle.mjs`, qui produit
`bin/phpurs.js` avec l’identité de toolchain et le cache de modules activés.
`bin/phpurs.js` est ignoré par Git et reconstruit à la demande.

Le runner benchmark `altbak.pub-phpurs/bin/php/run -c` utilise le même
`tools/bundle.mjs` après son `spago build` épinglé au compilateur hôte.

## Formatage et organisation

```sh
npm run format        # applique purs-tidy et Prettier
npm run format:check  # vérifie sans modifier
```

Les versions sont épinglées dans `package.json`. `.editorconfig` fixe les fins
de ligne et l’indentation ; `.tidyrc.json` et `.prettierrc.json` portent les
options des formateurs, dont une largeur cible de 100 colonnes.

Le périmètre est le code maintenu ici : `src/**/*.purs`, `src/**/*.js`,
`tests/codegen/**/*.mjs`, `tools/*.mjs`, les fichiers JSON racine et
`spago.yaml`. Conserver les fixtures importées de `tests/passing` et les audits
historiques dans leur forme d’origine.

- Placer chaque module PureScript selon son nom (`Phpurs.X` dans
  `src/Phpurs/X.purs`) et sa FFI JavaScript à côté.
- Nommer les fonctions selon leur rôle et extraire les étapes ayant une
  responsabilité propre, sans créer de façade ou d’abstraction sans usage.
- Garder les contrats publics et les invariants près du code qui les applique ;
  mettre à jour les liens de la documentation lors d’un déplacement.

## Tests

Vérification complète, avec compilation fraîche :

```sh
npm run check  # format:check, build, puis test:codegen
```

Après un build à jour, `npm run test:codegen` relance uniquement les tests.

Test ciblé :

```sh
node --test tests/codegen/direct/<fichier>.mjs
node --test tests/codegen/cli/<fichier>.mjs
node --test tests/codegen/ffi/<fichier>.mjs
```

Les familles et conventions sont décrites dans
[`tests/codegen/README.md`](../tests/codegen/README.md). Les helpers communs
sont dans `tests/codegen/fixtures/cli.mjs` (`temporaryRoot`, `runPhp`,
`snapshotFiles`, `snapshotText`).

Les tests CLI utilisent `bin/phpurs.js` par défaut et peuvent le remplacer via
`PHPURS_TEST_CLI`. Les tests d’exécution PHP désactivent OPcache CLI.

Les fixtures du langage et les packages PHP ont leurs propres runners :

```sh
./bin/test FunctionFFIBoundary     # fixture ciblée, bundle existant
./bin/test --skip-before=TCO      # suite à partir de TCO
./bin/test -c                    # rebuild et suite avec caches vidés
./bin/modtest                    # suites des bibliothèques sœurs
```

`bin/test` valide les sélections avant de préparer le projet et distingue les
échecs de compilation PureScript, génération PHP et exécution. Il affiche le
diagnostic du premier échec et s’arrête. Les tests isolés de son contrôle de flux
sont dans `tests/codegen/cli/language-runner.mjs` ; ils simulent les exécutables
sans compiler les fixtures. `tests/runner` est un espace jetable : ne pas y
conserver de sources personnelles.

`bin/modtest` compte les suites exécutables trouvées dans les packages frères,
signale leur absence et s’arrête au premier échec. Ses tests isolés vivent dans
`tests/codegen/cli/sibling-runner.mjs`.

Les wrappers des bibliothèques partagent `bin/test-package`, couvert par
`tests/codegen/cli/package-runner.mjs`. Le [guide des bibliothèques](libraries.md)
décrit la sélection/restauration de configuration, les tests FFI et les
commandes `format:packages` et `format:packages:check`.

## Benchmarks

Le runner et les fixtures de performance vivent dans `altbak.pub-phpurs` ; ils
ne font pas partie de `npm run test:codegen`.

```sh
cd ../../altbak.pub-phpurs
bin/php/run -c          # rebuild + 14 benchmarks
bin/php/run --run-only  # benchmarks sur la sortie existante
```

Comparer toute mesure de performance aux baselines officielles du
`README.md` d’altbak.pub, avec les mêmes workloads et options PHP. Pour un
refactor, vérifier aussi le comportement et les sorties PHP à entrées figées.

## Sorties générées

- `output/`, `.spago/`, `.purmeta/`, les caches et `bin/phpurs.js` sont ignorés.
- Le paquet npm est filtré par `.npmignore` ; ne pas y réintroduire les audits,
  tests ou caches.
- Les audits historiques restent dans `audit/` et sont indexés dans
  `audit/README.md`.

## Règles de refactor

- Un module extrait doit conserver l’API publique et les sorties PHP.
- Compiler et tests ciblés après chaque extraction.
- Vérifier une sortie PHP identique pour `--main App --no-cache` quand le
  générateur est touché.
- Ne pas mélanger déplacement de code et changement d’algorithme.
