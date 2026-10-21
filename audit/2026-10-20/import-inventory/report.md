# Inventaire des imports, exports et frontières FFI

Le 20 octobre 2026. Inventaire statique sans suppression.

## Résultat

- 39 fichiers PureScript et 13 fichiers JavaScript dans `src/`.
- **480 imports** explicites/qualifiés inspectés.
- **0 import explicite non utilisé** et **0 alias qualifié non utilisé**
  détectés par le scanner lexical.
- **0 export JavaScript supplémentaire** par rapport aux `foreign import`.
- Trois `foreign import data` n’ont volontairement pas d’export JS :
  `BuildProfile.Profile`, `BuildProfile.Span` et `OutputManifest.OutputTracker`.

| Fichier | Foreign data |
| --- | --- |
| `src/Phpurs/BuildProfile.js` | `Profile`, `Span` |
| `src/Phpurs/OutputManifest.js` | `OutputTracker` |

## Limites

Le scanner reconnaît les imports explicites mono-ligne, les alias qualifiés et
les exports JS simples. Il ne prouve pas :

- l’absence d’usage d’un import ouvert (`import Module` sans liste) ;
- les imports multi-lignes ou les opérateurs ;
- les exports PureScript utilisés uniquement par les tests ou la FFI ;
- les fichiers JavaScript dont les fonctions internes ne sont pas exportées.

Aucun fichier n’a été supprimé ou modifié. Les données brutes sont dans
`imports.json` et `ffi.json`.

## Suite

Prochaine micro-étape Phase 1 : relever la durée et le coût mémoire des tests
codegen, ou clarifier le chemin de build `npm run build` vs
`bin/php/run -c`. Aucun nettoyage de code avant ces mesures.
