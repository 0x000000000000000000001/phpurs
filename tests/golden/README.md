# Golden PHP corpus

Les cas de `cases/` rendent une entrée figée en un ensemble de fichiers PHP.
`expected/` contient la sortie de référence, commitée : toute variation devient
un diff relisible en revue.

```sh
npm run test:golden     # compare (échec si un octet change)
npm run golden:update   # réécrit expected/ après un changement voulu
```

Un cas est un module ESM exportant :

- `name` : nom du dossier dans `expected/` ;
- `render()` : fonction pure retournant `{ '<fichier>': '<contenu>' }`.

Les cas importent le compilateur compilé depuis `output/` : lancer
`npm run build` avant `npm run test:golden` sur un clone neuf. Le corpus ne
dépend ni de PHP, ni du fork TAST, ni de Spago : il compare uniquement la
sortie du générateur.

Règles :

- Un changement de sortie intentionnel met à jour `expected/` dans la même PR,
  et le diff correspondant est relu.
- Un nouveau cas couvre une famille de sortie stable (ADT, TCO, records, FFI,
  Aff, entrypoints). Ne pas dupliquer un cas déjà couvert par un test direct :
  le golden fige la sortie, les tests directs prouvent les contrats.
- Ne jamais normaliser la sortie (pas de trim, pas de réécriture de chemins) :
  le but est l'égalité octet pour octet.
