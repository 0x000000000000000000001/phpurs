# Hygiène du dépôt — artefacts et paquet npm

Le 20 octobre 2026. Inventaire sans suppression d’artefacts.

## État Git

Les gros artefacts locaux sont déjà ignorés par `.gitignore` :

| Artefact | Taille |
| --- | ---: |
| `node_modules/` | 149 Mo |
| `output/` | 99 Mo |
| `.purmeta/` | 54 Mo |
| `.spago/` | 4,1 Mo |
| `audit/` (suivi) | 7,4 Mo |
| `tests/` (suivi) | 3,9 Mo |
| `bin/phpurs.js` | 1,6 Mo |

Aucun fichier généré suspect n’est suivi par Git (`output`, `.purmeta`,
`.spago`, caches, `bin/phpurs.js`).

## Paquet npm

`npm pack --dry-run` avant correction :

- 10 569 199 octets empaquetés ;
- 163 120 256 octets décompressés ;
- 4 906 fichiers.

Le paquet incluait `.purmeta/`, `output/`, `audit/` et `tests/` parce que
`.npmignore` ne les excluait pas. `.npmignore` exclut désormais ces artefacts ;
le paquet ne garde que le CLI construit, les sources, les assets, les docs et
les fichiers de workspace :

| Mesure | Avant | Après |
| --- | ---: | ---: |
| Octets empaquetés | 10 569 199 | **747 612** |
| Octets décompressés | 163 120 256 | **2 596 529** |
| Fichiers | 4 906 | **76** |

`bin/phpurs.js` reste inclus dans le paquet, même s’il est ignoré par Git, car
`prepare` le reconstruit avant la publication.

## Décision

Aucun artefact local n’a été supprimé. La correction est limitée à
`.npmignore` ; elle réduit le paquet de **92,9 %** et évite de publier les
caches et les audits.

Prochaine micro-étape : inventorier les imports/exports sans usage, sans
suppression.
