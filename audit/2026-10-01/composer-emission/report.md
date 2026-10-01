# B3 — Contrat d'émission et collecte Composer

Référence : commit PHPurs `a84133d`. Comparaison avec le cleanup appliqué dans le checkout, le 1er octobre 2026.

## Changement

`Main` transmet à `mergeComposers` le répertoire de sortie, l'option FFI et les chemins des modules déjà chargés. Ces chemins sont triés par nom de module : l'ancien collecteur parcourait les dossiers de sortie dans cet ordre, qui peut différer de l'ordre des dépendances utilisé pour générer le PHP.

Le collecteur déduit directement les racines des packages. La relecture des CoreFn, la consultation de `.phpurs-cache.json` et le cache global des répertoires de collecte sont supprimés. Les racines Spago, FFI puis modules gardent leur ordre de fusion et leur déduplication ; la dernière exigence rencontrée gagne dans `require` comme dans `require-dev`.

Les bundles par entrée et `composer.json` sont écrits sous `outputDir`, y compris pour un chemin relatif imbriqué ou absolu. Les exigences du manifeste situé à la racine `--ffi` sont désormais collectées.

## Régressions

`tests/codegen/file-emission.mjs` invoque le vrai `Main` compilé sur deux petits modules TAST. `A.Main` dépend de `Z.Library`, avec une dépendance Composer commune dont les contraintes diffèrent : ce cas vérifie que l'ordre topologique ne change pas la priorité de fusion.

- Avant correction : le cas par défaut passe ; les sorties personnalisées relative et absolue échouent avec `ENOENT` sur `output/A.Main/main.bundle.php`.
- Après correction : les trois cas passent, avec contrôle de la liste des fichiers, des manifestes, de l'option FFI et de l'indépendance vis-à-vis d'un ancien output et d'un cache obsolète.
- Les entrées modulaires et bundle affichent chacune `42`, sans diagnostic PHP.
- `npm run build` réussit sans avertissement du compilateur PureScript ; `npm run test:codegen` passe les 17 contrôles rapportés par Node.

## Comparaison b8x

Entrée figée depuis `b8x/run/bak/php/output` : 2 684 CoreFn, 135 fichiers PHP FFI et 8 manifestes Composer, avec les chemins relatifs conservés et les dépendances `.spago` du backend PHP. Deux copies fraîches du même snapshot sont générées avec `--main Inter.Api.Main --bundle`, répertoire par défaut `output`.

Les deux générations terminent avec code 0 et sans erreur de décodage. Comparaison SHA-256 du contenu et de la liste des fichiers : **5 371 fichiers identiques**, dont **2 686 fichiers PHP**, **2 684 CoreFn** et **un manifeste Composer**. Aucun fichier ajouté, manquant ou modifié. Il s'agit d'une comparaison de génération ; les exécutions applicatives de cette validation sont celles des fixtures ci-dessus.

Empreintes SHA-256 :

- Snapshot : `feabedeaed784719b9214cf675de982ee95c4beacb869b83a608df0b6a4e95c6` — hash du JSON compact des couples `[chemin relatif, SHA-256 du fichier]`, triés par chemin.
- Bundle du backend avant : `223f4ba4f29bc52e17bd2623455d713ec0e8244189c09cfc42ea8ec8960eec39`.
- Bundle du backend après : `d483614dd897061b39ad998e8ad7160f46f7bb840d11d4b1aae4f75c79f62781`.

Les journaux et snapshots locaux sont dans `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b3-jzxtnkx0` (`before.log`, `after.log`, `comparison.json`).

Une seule paire de contrôle donne une finalisation de 1 382 → 230 ms et un temps backend total de 50 361 → 50 038 ms. Ces durées servent de relevé de validation, pas d'estimation stable du gain de compilation. La suppression du second parcours des CoreFn est structurelle ; aucune performance runtime n'est mesurée dans ce lot.
