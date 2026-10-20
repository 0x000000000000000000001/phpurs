# R7 — Fermeture des bundles à main explicite

Le 20 octobre 2026. Un bundle `--main App` contient désormais la **même
fermeture réelle** que `App/main.mod.php` : **108 modules**, dans le même ordre. Le
bundle automatique conserve la politique existante de tous les modules chargés.

## Résultat

| Bundle | Modules | Octets |
| --- | ---: | ---: |
| `App/main.bundle.php`, `--main App` | **108** | **1 700 126** |
| `bundle.php`, automatique | 306 | 5 596 736 |

Le bundle explicite représente **69,62 % d'octets en moins** et **198 modules en
moins**. L'ordre des namespaces du bundle est identique à l'ordre des
`require_once` de `main.mod.php`.

## Contrat

- La fermeture suit les imports CoreFn, car chaque `index.php` module charge ses
  imports. Les seuls requires d’entrée en listaient 77, mais le chargement réel
  en comptait 108 ; le bundle et l’entrée explicite en listent maintenant 108.
- La fermeture est calculée depuis les modules CoreFn déjà chargés, donc elle est
  aussi disponible en mode bundle-only sans émission modulaire.
- Les bundles automatiques sans `--main` restent complets, y compris les
  initialiseurs de modules non atteignables ; le test FFI qui exige ce
  comportement reste vert.
- Un bundle explicite charge donc les mêmes modules que le mode modulaire et
  n'exécute pas les initialiseurs hors fermeture.

## Validation

- Fermeture 108/108 identique et même ordre.
- `main.mod.php` et `main.bundle.php` valident chacun les 14 valeurs.
- `--bundle-only --main App` produit le même bundle de 108 modules et valide
  aussi les 14 valeurs.
- Le test `missing-ffi.mjs` couvre le bundle automatique complet et le bundle
  explicite filtré.
- Build sans warning ; **99/99** contrôles codegen.

## Suite

Les 198 modules hors fermeture sont encore chargés, optimisés et émis dans
`main.mod.php` et dans le corpus modulaire. La prochaine micro-étape R7 est de
filtrer les modules **avant optimisation** à partir de la fermeture CoreFn, en
conservant l'union des racines pour plusieurs mains, puis de mesurer le temps de
build et les octets. La DCE des bindings et le runtime partagé restent ensuite.

Artefacts :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-r7-bundle-20261020`.
