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

## Tests

Entrée complète :

```sh
npm run test:codegen
```

Test ciblé :

```sh
node --test tests/codegen/<fichier>.mjs
node --test tests/codegen/cli/<fichier>.mjs
node --test tests/codegen/ffi/<fichier>.mjs
```

Les familles et conventions sont décrites dans
[`tests/codegen/README.md`](../tests/codegen/README.md). Les helpers communs
sont dans `tests/codegen/fixtures/cli.mjs` (`temporaryRoot`, `runPhp`,
`snapshotFiles`, `snapshotText`).

Les tests CLI utilisent `bin/phpurs.js` par défaut et peuvent le remplacer via
`PHPURS_TEST_CLI`. Les tests d’exécution PHP désactivent OPcache CLI.

## Benchmarks

Le runner et les fixtures de performance vivent dans `altbak.pub-phpurs` ; ils
ne font pas partie de `npm run test:codegen`.

```sh
cd ../altbak.pub-phpurs
bin/php/run -c          # rebuild + 14 benchmarks
bin/php/run --run-only  # benchmarks sur la sortie existante
```

La performance n’est pas un critère du plan de cleanup actuel. Les mesures
servent uniquement à vérifier qu’un refactor ne change pas le comportement ou
les sorties PHP.

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
