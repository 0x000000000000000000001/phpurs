# B3 — Racines partagées et candidats FFI dédupliqués

Référence : checkout PHPurs après le [lot sans foreign](../ffi-empty/report.md), sur le commit `de27407`. La référence inclut sa garde non commitée ; son bundle est identifié ci-dessous. Comparaison du 1er octobre 2026.

## Changement

`Phpurs.PackagePaths` prépare une fois par build les racines de packages Spago et l'option `--ffi`. `Main` transmet les listes résultantes au résolveur PHP et à Composer. Le collecteur Composer reçoit directement ses racines ordonnées, avec celles déduites des chemins des modules déjà chargés.

La recherche PHP conserve la priorité : fichier adjacent, racines Spago, `--ffi`, puis dossier du projet. Dans chaque racine, `src/Foo/Bar.php` précède `src/Foo.Bar.php`, puis `Foo.Bar.php`. Chaque candidat est normalisé lexicalement et sondé une seule fois par recherche. Les préparations sont propres au build ; aucun cache global de racines n'intervient dans ce chemin PHPurs.

Les sources des modules continuent à donner le chemin FFI adjacent. Leurs racines supplémentaires servent à Composer, sans élargir le fallback FFI à tous les packages du graphe. Le code de résolution est local à PHPurs.

## Validation

- Avant changement, les fixtures d'émission échouent sur les sondes dupliquées : notamment `src/Solo.php`, qui peut être à la fois le chemin adjacent et deux variantes du fallback.
- Après changement, `file-emission.mjs` vérifie une seule découverte Spago partagée, zéro candidat dupliqué, le court-circuit des modules sans foreign et la collecte Composer. Les sorties par défaut, relative et absolue passent ; la dernière utilise aussi `--ffi` absolu.
- Les programmes générés en mode modulaire et bundle affichent `42`, sans diagnostic PHP.
- `package-paths.mjs` met des fichiers concurrents à chaque niveau de priorité, les retire successivement et vérifie le fichier sélectionné. Toute tentative d'énumération de répertoire pendant la recherche échoue dans ce test. Un fichier dans `node_modules` hors des racines admises reste introuvable.
- Les préparations successives dans un même processus respectent les changements d'option FFI et de dossier de travail.
- `npm run build` réussit sans avertissement PureScript ; `npm run test:codegen` passe les **19 contrôles** rapportés par Node.

## Comparaison b8x

Même snapshot de 2 684 CoreFn que les lots précédents, avec les mêmes FFI et manifestes, deux copies fraîches, `--main Inter.Api.Main --bundle`. Les deux générations terminent avec code 0, sans erreur de décodage.

Comparaison SHA-256 des contenus et de la liste : **5 371 fichiers identiques**, dont **2 686 PHP**, **2 684 CoreFn** et **un manifeste Composer**. Aucun fichier ajouté, manquant ou modifié.

| Compteur instrumenté | Avant | Après |
| --- | ---: | ---: |
| `fs.existsSync` sur candidats `.php` | 1 289 | 1 264 |
| Lectures de fichiers `.php` | 134 | 134 |
| `fs.readdirSync` | 4 | 2 |

L'instrumentation synchronise les exports natifs ESM de Node pour compter également les lectures importées par nom. Ces compteurs décrivent les appels aux API de fichiers, pas les lectures physiques du disque. La seule paire instrumentée donne 45 686 → 48 379 ms pour le backend total ; elle ne démontre pas un gain stable de temps de compilation. La réduction des parcours et des sondes est directement mesurée.

Empreintes SHA-256 des bundles du backend :

- Avant : `7a73d55366e40f67a1ffc6f12f17381ae9367d627c23df1e8318aa7250a9d198`.
- Après : `8648704981c4b0c1aff186430fe29e7f242d751815c62b762c58eae0de338ffc`.

Artefacts locaux : `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b3-paths-gjdek1m5` contient les copies avant/après, les journaux, `count-paths.mjs`, les `path-counts.json` et `comparison.json`.
