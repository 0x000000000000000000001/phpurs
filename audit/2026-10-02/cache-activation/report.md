# B1 — Activation du cache dans la CLI

Base de départ PHPurs : `890652a9675f8ab8a3b368364ea338c0c6e5fb7b`. PBO : `0f41544464ec0f42e6cb0dd77b206852813f904f`, sans changement dans ce lot. Le bundle exécuté et le bundle final ont la même SHA-256 : `64f7207f64900580743da73315576718ee22197094ae9dc39036ab2c1c7d0306`.

## Intégration

La CLI packagée utilise désormais le cache par défaut, sous `<outputDir>/.phpurs-cache/v1`. `--no-cache` évite les lectures/écritures d'états et fournit un témoin de compilation complète. Les sorties PHP conservent leur comparaison octet par octet dans les deux modes. La finalisation de Composer et des entrypoints utilise les entrées courantes et les contributions générées/restaurées.

- `Phpurs.BuildInputs` lit chaque CoreFn une seule fois et calcule l'empreinte et le module décodé depuis le même buffer. Il reprend la politique de chargement bornée `GOPURS_JOBS`, conserve l'ordre des résultats et emploie le sorter PBO. Les doublons sont détectés avant leur réduction dans l'index du sorter.
- Chaque FFI déclarée est résolue avant lookup, même sur un hit ou une ancienne absence. Le fichier retenu fournit à la fois ses octets de clé et la source UTF-8 de codegen, sans relecture. Les modules sans déclaration foreign gardent le raccourci sans recherche.
- `Phpurs.BuildCache` calcule le plan v1, branche les hooks et rapporte `hits, misses, stores`. Les erreurs de données optionnelles restent des misses ; les échecs de stockage n'empêchent pas la génération. Une entrée incomplète/ambiguë ou un plan non supporté désactive tout le cache pour cette invocation.
- `tools/bundle.mjs` remplace la commande de bundling directe. Il construit un unique `bin/phpurs.js` contenant la source CommonJS de tout le compilateur et de ses dépendances JavaScript. Seuls les built-ins Node peuvent rester externes. Le démarrage hache la source en mémoire, les identités PHPurs/PBO et sa propre fonction, puis évalue cette même source. Il ne relit pas l'exécutable sur disque pour identifier un code déjà chargé.

Les imports de développement via `Main.main` conservent des hooks désactivés faute d'identité immuable fournie par leur chargeur. La CLI appelle `mainWithToolchain` ; les tests d'état peuvent continuer à appeler `mainWithCache`. Voir le [contrat de cache](../../../docs/cache.md).

## Régressions

`npm run build` réussit sans avertissement PureScript. `npm run test:codegen` passe **46 contrôles**, dont dix nouveaux contrôles CLI :

1. Génération froide, hits complets, réparation des sorties et témoin `--no-cache` dans les trois modes d'émission et avec arguments groupés/répertoire `./output`.
2. Chargement avec `GOPURS_JOBS=4` : mêmes clés et ordre ; un seul read de chaque CoreFn et FFI retenue.
3. Modification CoreFn de même taille et mtime, propagation d'une constante à un consommateur inchangé, whitespace brut et directives exportées.
4. FFI aux octets UTF-8 différents mais au texte décodé identique, source absente puis vide, apparition d'un chemin adjacent prioritaire et retour à un ancien chemin/contenu.
5. Partition par options effectives, source/versions du compilateur et hôte ; réutilisation lorsque deux options FFI donnent effectivement les mêmes racines.
6. Remplacement CoreFn/FFI après lecture mais avant hachage/codegen : le build utilise les octets capturés et le suivant invalide correctement. Le cas FFI a aussi été renforcé et repassé sur un miss forcé.
7. Remplacement de l'exécutable sur disque pendant le build : l'identité du code déjà chargé reste celle du build en cours ; l'invocation suivante utilise la nouvelle identité.
8. Entrées manquantes/corrompues, stockage indisponible, changement de graphe, doublons et cycles avec repli complet.

Les fixtures exécutent le PHP modulaire et bundle et vérifient les résultats initiaux/modifiés (`43`, `44`, `52`, `99`). Les tests d'état précédents continuent à vérifier les refs, les purmeta, la sérialisation et les erreurs d'E/S des sorties. `git diff --check` réussit.

## Essai b8x

[`verify.py`](verify.py) utilise le snapshot figé du [lot d'état](../../2026-10-01/module-state/report.md), avec 2 839 fichiers d'entrée, dont 2 684 CoreFn, 147 PHP et huit manifestes Composer. Les chemins PHP sont préservés. Le manifeste trié au format JSON Python porte l'empreinte `9e159394a364c95af49aa1d66cdcc7d3c66ec410acbaf70b19f96bfda98e7372` ; son encodage diffère du manifeste compact du lot précédent.

Le script copie le bundle dans les artefacts et invoque cette copie autonome, avec `--main Inter.Api.Main --bundle` et `GOPURS_JOBS=1`. Il reprend les deux mutations exactes de M0, puis compare chaque état avec un témoin `--no-cache` généré après retrait des sorties PHP. Les comparaisons portent sur la liste des fichiers et leurs octets ; les blobs internes `.phpurs-cache` sont exclus de la liste des sorties compilées. Les données sources figées sont revérifiées à la fin.

Le témoin initial reproduit les **5 371 fichiers** du lot précédent. Les trois scénarios avec cache reproduisent chacun les **5 371 fichiers de leur témoin sans cache** : 2 684 CoreFn, 2 686 PHP et un manifeste Composer.

| Scénario avec cache | Hits | Misses / modules réoptimisés | États stockés | PHP écrits | Fichiers identiques au témoin |
| --- | ---: | ---: | ---: | ---: | ---: |
| Remplissage | 0 | 2 684 | 2 684 | 0 | 5 371 |
| Aucun changement | **2 684** | **0** | 0 | **0** | 5 371 |
| Feuille M0 modifiée | 1 851 | 833 | 833 | 2 | 5 371 |
| Dépendance M0 modifiée | 834 | 1 850 | 1 850 | 31 | 5 371 |

Les listes de codegen correspondent exactement aux suffixes prévus par les clés v1. La feuille est `Inter.Api.Main.handle` (`Not found.` → `Not found (M0 leaf).`) ; la dépendance est `Core.Message.Command.Command.defaultMaxConcurrencyRetries` (`50` → `51`). Les 31 PHP modifiés dans le second cas incluent les 29 consommateurs au CoreFn inchangé observés dans M0.

Le rebuild identique conserve les **2 686 mtime PHP**. Chaque invocation continue à lire les **2 684 CoreFn**, et les rebuilds comparent les **135 282 480 octets** des sorties PHP précédentes. PBO republie encore **2 684 `.purmeta`** par build ; le dossier a été supprimé avant l'essai à 100 % de hits. Le cache initial occupe **570 659 606 octets, soit 544,2 Mio**.

## Relevés de durée et de mémoire

Node `24.8.0`, darwin/arm64, sans `NODE_OPTIONS`. Une seule exécution de chaque cas a été relevée ; il s'agit de mesures de validation sur machine partagée, pas de médianes ni d'un benchmark ABBA. Les témoins sans cache repartent sans PHP et les rebuilds comparent des sorties déjà présentes. Les phases et traces complètes sont conservées.

| Invocation | Backend (s) | Pic RSS (Mio) |
| --- | ---: | ---: |
| Témoin inchangé, sans cache | 49,942 | 3 353,4 |
| Remplissage du cache | 57,683 | 3 669,7 |
| Rebuild inchangé | **13,909** | 3 127,1 |
| Feuille modifiée, avec cache | 30,545 | 3 969,3 |
| Témoin feuille, sans cache | 49,653 | 3 386,9 |
| Dépendance modifiée, avec cache | 79,715 | 3 567,3 |
| Témoin dépendance, sans cache | 72,797 | 3 393,6 |

Le cas inchangé évite effectivement tout codegen et son temps total baisse dans ce relevé. Le remplissage, les sérialisations des misses et la politique de suffixe ont un coût : le cas dépendance est ici plus lent avec cache, et le pic mémoire du cas feuille est plus élevé. Les compteurs et comparaisons de sorties sont les résultats reproductibles recherchés dans cette tranche ; affiner les performances demandera une campagne répétée.

## Reproduction

```sh
python3 audit/2026-10-02/cache-activation/verify.py \
  --snapshot /chemin/vers/snapshot \
  --reference-output /chemin/vers/temoin-du-lot-etat \
  --artifacts /chemin/vers/dossier-neuf
```

Le [résumé JSON](results.json) contient tous les relevés (`cache = [hits, misses, stores]`). Les journaux, listes ordonnées de modules, traces E/S, témoins et copie de l'exécutable sont dans `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b1-active-KpTxcwqF`.

La dernière case B1 reste consacrée au traitement complet des suppressions de modules et des changements de main, au-delà de l'invalidation des clés vérifiée ici.
