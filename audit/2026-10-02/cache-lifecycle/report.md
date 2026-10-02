# B1 — Suppressions de modules et changements de main

Base PHPurs : `7d473e72b18b32c665d85ea96daff1bfbad7ef1e`. PBO : `0f41544464ec0f42e6cb0dd77b206852813f904f`. Le bundle de ce lot porte la SHA-256 `940845b08dafb804d24f4456cfb78f4654c3be355b692d1d07ccf3ffef045a6b`.

## Traitement des sorties

`Phpurs.OutputManifest` suit les quatre formes de PHP émises : modules, entrées modulaires, entrées bundle et bundle global. La publication partagée de `ModuleState` enregistre aussi les sorties restaurées sur cache hit. Chaque enregistrement contient chemin relatif, famille d'émission et SHA-256 des octets UTF-8 émis.

Après finalisation des entrées et de Composer, les anciennes sorties absentes du build courant sont retirées si elles appartiennent à une famille active et correspondent encore à leur empreinte. Les fichiers modifiés par l'utilisateur, les liens symboliques remplacés et les fichiers non suivis sont conservés. Les familles désactivées conservent leurs fichiers et leur suivi ; leur réactivation permet le nettoyage différé. Cela respecte notamment la conservation des sorties modulaires par `--bundle-only`.

Le manifeste `<output>/.phpurs-outputs.json` est indépendant du cache d'états ; `--no-cache` applique donc le même cycle de vie. Il est versionné, trié et remplacé atomiquement seulement lorsque ses octets changent. Un manifeste absent ou invalide n'autorise aucune suppression. Des entrées CoreFn incomplètes ou dupliquées reportent le nettoyage. Les erreurs d'émission, de nettoyage et de publication du manifeste font échouer le build ; l'ancien manifeste reste disponible pour réessayer. L'atomicité porte sur le manifeste, pas sur l'ensemble de l'arbre de sortie.

`Main` valide également un `--main` explicite avant l'émission : le module doit être chargé et exporter `main`. La découverte automatique suit les exports courants. La suppression d'un module signifie ici le retrait de son CoreFn des entrées chargées. Les dossiers de modules, autres fichiers, états content-addressed et métadonnées PBO suivent leurs contrats existants ; l'appartenance au build courant de PBO empêche la relecture d'anciennes implémentations `.purmeta`.

Voir le [contrat détaillé](../../../docs/cache.md#output-lifecycle-and-main-selection).

## Régressions et exécution PHP

`npm run build` réussit sans avertissement PureScript ; `npm run test:codegen` passe **55 contrôles**. Les neuf nouveaux contrôles de cycle de vie couvrent :

1. Suppression du seul CoreFn d'un module, ancien PHP conservé sur disque avant le rebuild : retour à trois hits complets, suppression de l'ancien `index.php` et actualisation des exigences Composer. Même scénario avec `--no-cache`, puis suppression de tous les modules.
2. Sélections explicites de deux mains et retour à la découverte automatique ; perte d'un export `main`, suppression d'un module main, absence de tout main et bundle global sans appel.
3. Conservation des familles désactivées, puis nettoyage à leur réactivation, avec sortie personnalisée absolue et arguments groupés.
4. Main explicite absent ou sans export : échec avant toute émission, avec et sans cache.
5. CoreFn illisible en JSON ou dupliqué, échec d'émission et reprise avec l'ancien manifeste.
6. Sortie obsolète modifiée en octets bruts, PHP utilisateur non suivi, fichier/dossier remplacé par un lien symbolique et sortie déjà absente.
7. Manifeste absent, JSON endommagé, version inconnue ou chemin interdit : conservation des anciennes sorties non prouvées.
8. Erreurs injectées de lecture du manifeste (`EIO`), suppression (`EACCES`) et renommage (`ENOSPC`) : propagation, ancien manifeste intact, nettoyage des temporaires et reprise réussie.

Les témoins frais repartent des seuls CoreFn courants **aux mêmes chemins**, sans sorties, historique de propriété ou cache. Les listes de fichiers et les octets PHP/Composer/manifeste sont identiques. Les entrées modulaires et bundle sont exécutées avec PHP 8.5.4 et OPcache CLI désactivé ; les fixtures attendent `43` et `alternate`. Les contrôles antérieurs d'invalidation et d'exécution continuent à passer. Le contrôle de suppression a été renforcé après la suite complète pour couvrir explicitement le nettoyage sans cache, puis repassé avec succès.

Le test ciblé échoue sur la copie immuable du bundle d'activation précédent (`64f7207f64900580743da73315576718ee22197094ae9dc39036ab2c1c7d0306`) : `Retired/index.php` existe encore après suppression du CoreFn et retour à trois hits. Le journal est conservé dans les artefacts (`before.log`).

## Protocole b8x

[`verify.py`](verify.py) reprend le snapshot figé de 2 839 fichiers du [lot d'état](../../2026-10-01/module-state/report.md), avec 2 684 CoreFn. Il copie l'exécutable et les entrées dans un dossier d'artefacts isolé. Les commandes utilisent `--bundle`, `GOPURS_JOBS=1`, puis successivement :

1. `--main Inter.Api.Main`, remplissage et rebuild inchangé ;
2. `--main Inter.Cli.Ping.Main`, sans modification du graphe ;
3. retrait de `Inter.Api.Main/corefn.json` uniquement, les anciennes sorties PHP restant en place avant compilation ;
4. restitution exacte du CoreFn et retour à `--main Inter.Api.Main`.

Le graphe vérifie qu'aucun autre module n'importe `Inter.Api.Main`. Chaque nouvel état est comparé octet par octet à un témoin `--no-cache` frais aux mêmes chemins. Les blobs `.phpurs-cache` sont exclus des sorties comparées. Les listes de modules, empreintes de sorties, traces E/S et phases chronométrées sont conservées. Les compteurs `reads.php` hérités de M0 portent sur les comparaisons asynchrones d'émission ; les lectures synchrones du nettoyage ne sont pas incluses. Les suppressions sont vérifiées par la différence des listes de fichiers.

Le test b8x porte sur la génération et la comparaison des sorties ; l'exécution PHP est vérifiée par les fixtures isolées ci-dessus.

## Résultats b8x

Les trois états comparés directement reproduisent la liste et les octets de leur témoin frais. Le retour final retrouve les empreintes exactes de l'état initial, avec restauration complète depuis le cache.

| État avec cache | Hits | Misses / modules générés | PHP écrits | PHP retirés | Fichiers identiques |
| --- | ---: | ---: | ---: | ---: | ---: |
| Remplissage, main API | 0 | 2 684 | 2 686 | 0 | 5 372, témoin frais |
| Inchangé | **2 684** | **0** | **0** | 0 | 5 372, état initial |
| Main API → Ping | 0 | 2 684 | **2** | **2** | 5 372, témoin frais |
| Suppression du module API | 0 | 2 683 | **1** | **1** | 5 370, témoin frais |
| Restitution module/main API | **2 684** | **0** | **3** | **2** | 5 372, état initial |

Les 5 372 fichiers incluent 2 684 CoreFn, 2 686 PHP, `composer.json` et le nouveau manifeste de propriété : un fichier de plus que lors du lot d'activation. Après suppression, il reste 2 683 CoreFn et 2 685 PHP, plus les deux manifestes. Les données sources figées sont identiques après l'essai ; leur empreinte de manifeste reste `9e159394a364c95af49aa1d66cdcc7d3c66ec410acbaf70b19f96bfda98e7372`.

Le changement de main retire les deux entrées API et écrit les deux entrées Ping ; les 2 684 modules PHP restent identiques. Le retrait du CoreFn API supprime son `index.php` et ne réécrit que le bundle Ping. La restitution à 100 % de hits répare les trois sorties API absentes et retire les deux entrées Ping. Le rebuild inchangé préserve les **2 686 mtime PHP** ; les états de cache initiaux occupent **570 659 606 octets (544,2 Mio)**.

Les clés v1 partitionnent le main et le graphe complets : les nouvelles sélections/graphes ont donc tous leurs modules en miss. Revenir aux entrées/options exactes précédentes réutilise leurs états conservés. PBO republie toujours les `.purmeta` des 2 684 ou 2 683 modules courants.

## Relevés de durée et de mémoire

Node 24.8.0, darwin/arm64, sans `NODE_OPTIONS`. Ce sont des relevés uniques de validation sur machine partagée, avec la suite de régressions exécutée pendant le début du remplissage. Les témoins sans cache partent sans sorties PHP ; les rebuilds comparent leurs sorties existantes. Ces relevés ne constituent pas une campagne de performance répétée.

| Invocation | Backend (s) | Pic RSS (Mio) |
| --- | ---: | ---: |
| Remplissage | 58,462 | 3 756,5 |
| Témoin initial sans cache | 57,213 | 3 365,2 |
| Inchangé, 100 % hits | 19,122 | 3 247,5 |
| Changement de main, cache | 59,473 | 3 813,0 |
| Témoin changement de main | 49,617 | 3 220,8 |
| Suppression de module, cache | 56,405 | 3 695,9 |
| Témoin suppression | 45,746 | 3 464,0 |
| Retour à l'état initial, 100 % hits | 13,444 | 3 294,7 |

Les deux changements qui invalident tout le plan restent ici plus coûteux avec sérialisation des nouveaux états. Les hits évitent effectivement l'optimisation et la génération. Le [résumé JSON](results.json) conserve les phases, RSS, compteurs et chemins affectés ; `cache` y représente `[hits, misses, stores]`. `git diff --check` réussit et le bundle final est identique à la copie exécutée.

## Reproduction

```sh
npm run build
npm run test:codegen
python3 audit/2026-10-02/cache-lifecycle/verify.py \
  --snapshot /chemin/vers/snapshot \
  --artifacts /chemin/vers/dossier-neuf
```

Artefacts locaux : `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b1-lifecycle-52kLzH86`, dont `b8x/` pour la campagne réelle et sa copie immuable du compilateur.
