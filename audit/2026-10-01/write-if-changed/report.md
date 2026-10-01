# B1 — Préserver les fichiers PHP identiques

Référence : checkout PHPurs propre au commit `b2ec0bf1662119458c358a7350ab2e6fed2abfd3`, incluant le [lot bundle seul](../bundle-only/report.md). Validation du 1er octobre 2026.

Environnement : Node `24.8.0`, PHP CLI `8.5.4` pour les fixtures (OPcache CLI désactivé), compilateur hôte PureScript `0.15.16`, PBO `0f41544464ec0f42e6cb0dd77b206852813f904f`.

## Contrat

`Phpurs.FileEmission.writeTextFileIfChanged` reçoit un chemin et le PHP généré. Il encode la chaîne en UTF-8, lit les octets du fichier existant et ne lance une écriture que si les octets diffèrent ou si le fichier est absent. Un fichier identique conserve son contenu et sa date de modification, quelle que soit cette date.

Les quatre sites d'émission de `Main` utilisent ce helper : `index.php`, `main.mod.php`, `main.bundle.php` et `bundle.php`. La comparaison utilise les buffers Node sans décodage de l'ancien contenu : une séquence UTF-8 invalide ne peut ainsi se confondre avec un caractère de remplacement légitimement généré. Les écritures sont attendues dans `Aff`. Seul `ENOENT` signifie une sortie absente ; les autres erreurs de lecture et les erreurs d'écriture remontent au build.

Ce premier lot B1 intervient après l'optimisation, la traduction et l'impression. `onSkipModule` renvoie encore `Nothing` : la réutilisation des états du compilateur et leur clé d'invalidation restent les étapes suivantes du backlog.

## Régressions

`tests/codegen/file-emission.mjs` instrumente les écritures PHP synchrones et asynchrones, avec synchronisation des exports ESM natifs. Chaque variante d'émission répète sa génération dans le même arbre. Les dates des PHP sont fixées à une valeur ancienne et relues avant le rebuild, pour éviter une dépendance à la résolution de l'horloge ou à une pause artificielle.

- Les sept combinaisons de mode et de destination vérifient zéro écriture PHP, les mêmes octets et les mêmes `mtime` au rebuild identique.
- Une modification FFI `41 → 42`, en conservant son mtime, ne réécrit que `Z.Library/index.php` et les trois bundles contenant ce module. Les entrées modulaires et bundlées passent de `42` à `43` à l'exécution PHP.
- Une modification de l'option d'autoloader ne réécrit que les entrées `main.mod.php` et `main.bundle.php` des deux mains.
- Une altération de même taille, une altération UTF-8 dont le décodage ressemble exactement au contenu attendu et deux bundles supprimés sont corrigés. Tous les octets précédemment attendus sont restaurés, puis le rebuild suivant ne réécrit rien.
- Les erreurs injectées `EACCES` et `EIO` en lecture échouent avant toute écriture ; `ENOSPC` en écriture remonte avec sa cause. Les diagnostics de phase et de total sont marqués `(failed)` et les processus terminent avec code 1.
- Avant le changement de code, les sept variantes échouent sur les écritures superflues du rebuild ; la régression d'erreur de lecture échoue également car l'ancien driver écrit sans lire la sortie précédente.

`npm run build` réussit sans avertissement PureScript ; `npm run test:codegen` passe les **24 contrôles** rapportés par Node. `git diff --check` réussit.

## Comparaison b8x

Même snapshot figé de 2 684 CoreFn, FFI PHP et manifestes Composer que les lots précédents. Une copie fraîche est générée avec `--main Inter.Api.Main --bundle`, puis deux nouveaux processus reprennent cet arbre, d'abord avec les mêmes options, ensuite avec `--bundle-only`. Les trois processus terminent avec code 0 et sans erreur de décodage.

La liste et les octets des **5 371 fichiers** sont identiques à la référence après chacune des trois invocations : 2 684 CoreFn d'entrée, 2 686 PHP et un manifeste Composer. Les **2 686 mtime PHP** sont préservés pendant chacun des deux rebuilds, y compris les anciens fichiers modulaires lors du rebuild bundle-only.

| Compteur | Sorties fraîches, `--bundle` | Rebuild identique, `--bundle` | Rebuild identique, `--bundle-only` |
| --- | ---: | ---: | ---: |
| Modules retraités | 2 684 | 2 684 | 2 684 |
| Écritures PHP | 2 686 | **0** | **0** |
| Octets PHP écrits | 135 282 480 | **0** | **0** |
| Tentatives de lecture des sorties PHP | 2 686 | 2 686 | 1 |
| Lectures réussies des sorties PHP | 0 | 2 686 | 1 |
| Octets relus pour comparaison | 0 | 135 282 480 | 66 548 063 |
| Écritures du manifeste Composer | 1 | 1 | 1 |
| Octets du manifeste Composer | 191 | 191 | 191 |
| Backend total, ms | 49 761 | 46 779 | 44 499 |
| Pic RSS, KiB | 3 302 640 | 3 492 976 | 3 266 096 |

Le résultat établi est la suppression des écritures de PHP identique, avec préservation des octets et des dates. La comparaison ajoute la lecture des sorties existantes et conserve temporairement les buffers nécessaires ; tous les modules sont encore retraités. Les durées et RSS ci-dessus sont des relevés uniques de processus instrumentés, pas une estimation stable du gain de compilation ou du surcoût mémoire. L'exécution PHP est vérifiée sur les fixtures ; la validation b8x porte sur la génération et la comparaison différentielle.

## Références et artefacts

Empreintes SHA-256 des bundles du backend :

- Référence : `0f3c4feb815f6a4e9259dc700819937bbbd4f9508b94bee9021240a84974935a`.
- Après : `9d0f3b08f089e5d07565ac357ca7764c0b2a453a9e3373cf3db1ee3637af13ca`.

Le snapshot TAST/FFI/Composer est conservé dans `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b3-jzxtnkx0/snapshot`. La sortie de référence se trouve dans `phpurs-b3-bundle-only-7t4f0472/both/b8x/output`, sous le même dossier temporaire.

Les artefacts de ce lot sont dans `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b1-emission-UhRlI7AZ` : `validate.py`, `count-io.mjs`, `backend-after.mjs`, copie `after`, journaux et compteurs par invocation, puis `comparison.json`. Le script crée une copie fraîche du snapshot, génère les sorties, compare la liste et chaque fichier avec la référence, fixe les mtime PHP, puis vérifie les deux rebuilds.
