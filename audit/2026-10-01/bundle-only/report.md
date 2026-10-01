# B3 — Émission bundle seul

Référence : checkout PHPurs après le [lot des chemins](../package-paths/report.md), sur le commit `de27407` avec les lots précédents non commités. Le bundle de référence est identifié ci-dessous. Comparaison du 1er octobre 2026.

## Contrat

`--bundle-only` est un flag propre à PHPurs. Il suffit à activer l'émission des bundles ; lorsqu'il accompagne `--bundle`, il prend priorité pour la sélection des fichiers. Les arguments groupés transmis par Spago suivent la même normalisation que les options partagées.

`Main` distingue `emitBundle` et `emitModules`. Bundle-only évite l'impression et l'écriture des `index.php` et des `main.mod.php`, ainsi que la préparation du graphe utilisé pour ces entrées modulaires. Les modules continuent à être optimisés et traduits dans leur ordre habituel pour construire le bundle et sa table d'arités.

- Avec `--main`, un `main.bundle.php` est produit pour cette entrée.
- Sans `--main`, les modules exportant `main` reçoivent chacun leur bundle exécutable ; le `bundle.php` global contient les modules sans appeler main.
- Le manifeste Composer est produit dans tous les modes et respecte `--output`.
- Le mode contrôle les écritures du build courant et préserve les fichiers déjà présents. Les comparaisons de listes de sorties utilisent donc des arbres d'entrée frais.

## Régressions

`file-emission.mjs` couvre sept combinaisons : mode modulaire par défaut ; `--bundle` et `--bundle-only` avec sortie par défaut, relative ou absolue. Deux modules exportent `main`. Le cas absolu transmet `--bundle --bundle-only` dans une seule chaîne d'arguments, avec un main explicite et une FFI externe.

- Avant changement, les trois cas bundle-only échouent sur la liste des fichiers : le flag est ignoré et les fichiers modulaires sont émis.
- Après changement, les listes correspondent exactement au mode demandé. Les entrées exécutables produisent respectivement `42` et `second`, sans diagnostic PHP. Le bundle global ne produit aucune sortie.
- Une seconde génération bundle-only conserve deux fichiers modulaires sentinelles et produit le même bundle, indépendamment de leur contenu.
- Les contrôles FFI, de partage des racines et de collecte Composer restent exercés dans chaque combinaison.
- `npm run build` réussit sans avertissement PureScript ; `npm run test:codegen` passe les **23 contrôles** rapportés par Node.

## Comparaison b8x

Même snapshot de 2 684 CoreFn que les lots précédents, avec les mêmes FFI et manifestes. Deux copies fraîches sont générées par le nouveau backend avec `--main Inter.Api.Main`, puis respectivement `--bundle` et `--bundle-only`. Les deux processus terminent avec code 0, sans erreur de décodage.

Le mode `--bundle` produit **5 371 fichiers identiques**, contenus et liste, à la sortie de référence du lot précédent. La sortie bundle-only est exactement son sous-ensemble après retrait des 2 684 `index.php` et du `main.mod.php` : **2 686 fichiers**, soit les 2 684 CoreFn d'entrée, un bundle et un manifeste Composer. Ces fichiers conservés sont identiques octet pour octet à ceux du mode `--bundle`.

| Compteur d'écriture instrumenté | `--bundle` | `--bundle-only` |
| --- | ---: | ---: |
| Écritures PHP | 2 686 | 1 |
| Octets PHP | 135 282 480 | 66 548 063 |
| Écritures du manifeste Composer | 1 | 1 |
| Octets du manifeste Composer | 191 | 191 |

Les compteurs interceptent les écritures Node synchrones et asynchrones, avec synchronisation des exports ESM natifs. Les nombres de fichiers et tailles finales confirment les compteurs. **68 734 417 octets PHP** et **2 685 écritures PHP** sont ainsi évités sur ce cas à un main explicite.

La paire instrumentée donne 46 215 → 44 643 ms de backend total. Ce relevé unique ne constitue pas une estimation stable du gain de compilation ; le résultat établi est la réduction de l'impression et des écritures modulaires. L'exécution PHP a été validée sur les fixtures, pas sur l'application b8x.

Empreintes SHA-256 des bundles du backend :

- Référence : `8648704981c4b0c1aff186430fe29e7f242d751815c62b762c58eae0de338ffc`.
- Après : `0f3c4feb815f6a4e9259dc700819937bbbd4f9508b94bee9021240a84974935a`.

Artefacts locaux : `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b3-bundle-only-7t4f0472` contient les copies `both` et `only`, les journaux, `count-writes.mjs`, les `write-counts.json` et `comparison.json`. La sortie de référence est conservée dans le dossier `after/b8x/output` du lot des chemins.
