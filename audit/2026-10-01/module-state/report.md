# B1 — Sauvegarde et restauration de l'état de module

Base PHPurs : `6c1f89a44cb8550bfae3b0e62c43b28dfc6bb944`. PBO : `0f41544464ec0f42e6cb0dd77b206852813f904f`, sans modification dans ce lot. Validation du 2 octobre 2026, dans la continuité des campagnes B1 du 1er octobre.

## Contrat livré

`Phpurs.ModuleState` regroupe le `BackendModule` PBO complet, les arités locales et les deux formes de PHP imprimé sélectionnées. Sa publication est commune aux modules fraîchement générés et aux hits : restauration de `globalAritiesRef`, de `backendModulesRef` pour la reachability modulaire et de `bundleContentRef`, dans l'ordre du builder. Les arités foreign ont priorité sur celles du code généré ; la contribution courante a priorité sur les modules précédents.

`Phpurs.ModuleCache` sauvegarde ce contenu dans `<répertoire>/v1/<clé>.bin`. Le format versionné contient la clé, le nom du module, une empreinte SHA-256 du payload et une table de graphe sérialisée par V8. Le codec restaure les prototypes des constructeurs PureScript, conserve les références partagées et parcourt les expressions profondes sans récursion. Il ne marque pas les objets vivants de l'optimiseur. Ce format est distinct du `.purmeta` privé de PBO.

Une entrée absente, illisible, incompatible, corrompue ou dépourvue du PHP demandé donne un miss. L'écriture passe par un fichier temporaire voisin, puis un renommage ; un échec conserve l'ancienne entrée complète. Les erreurs des sorties PHP restent fatales au build. Le [contrat détaillé](../../../docs/cache.md) documente les responsabilités du caller et le format.

`Main.mainWithCache` expose des hooks de chargement/sauvegarde pour exercer la branche cachée réelle du builder. PBO continue à republier les implementations dans `.purmeta` et à accumuler les directives sur cette branche, qui saute `onCodegenModule`. La CLI ordinaire utilise encore des hooks désactivés. L'activation automatique avec hachage/décodage des mêmes octets d'entrée est l'étape B1 suivante.

## Régressions exécutables

Cinq nouveaux contrôles dans `tests/codegen/module-cache.mjs` portent le total à **36** :

- Round-trip des constructeurs et du partage d'objets, nombre `-0`, expression profonde de 20 000 niveaux, priorité des arités et refus d'un état incomplet avant publication.
- Entrées absentes, mauvais module/clé/version, troncature, corruption, émission incomplète, erreur de stockage et interruption du remplacement atomique avec préservation de l'ancienne entrée et nettoyage du temporaire.
- Pour chacun des modes modulaire, mixte et bundle-only : témoin sans cache, remplissage avec comparaison structurelle intégrale des états relus, hits complets et mélanges hits/misses dans de nouveaux processus Node.

La fixture place une constante importée, une fonction avec directive exportée `never`, une application partielle FFI et un effet dans trois modules. Une dépendance cachée fournit l'état aux consommateurs réoptimisés. Les entrées modulaires et bundle affichent `43`, sans diagnostic PHP.

Les essais suppriment réellement le dossier `.purmeta` avant les builds restaurés/mélangés, conservent les dates des PHP identiques, reconstruisent un PHP absent et les entrées/bundles altérés, et injectent des erreurs de lecture/écriture des sorties sur un hit. Ces erreurs interrompent le build au lieu de provoquer une réoptimisation de repli.

`npm run build` réussit sans avertissement PureScript. `npm run test:codegen` passe les 36 contrôles ; la suite d'état repasse après renforcement des suppressions `.purmeta`. `git diff --check` réussit.

## Corpus b8x

Le snapshot temporaire des campagnes précédentes n'était plus présent. [`verify-b8x.mjs`](verify-b8x.mjs) reconstitue donc une nouvelle copie figée depuis `b8x/run/bak/php/output`, les FFI adjacentes et les dépendances PHP sauvegardées sous `b8x/run/bak/php/.spago`. Les chemins `../phpurs/...` sont préservés. Les arbres générés et les entrées de cache restent dans un répertoire d'artefacts isolé.

Cette copie contient **2 839 fichiers** : 2 684 CoreFn (213 935 062 octets), 147 PHP et huit manifestes Composer. Elle inclut davantage de PHP non sélectionnés que l'ancien snapshot. La résolution retrouve les mêmes effectifs : 2 385 modules sans déclaration foreign, 165 sources absentes et 134 sources sélectionnées. Le manifeste de cette nouvelle copie a l'empreinte `222025243e42a32a94ee7a3973f0cfdfc064c4ec6da70fd81ea4fc6a58b91872`.

L'audit utilise les modules JavaScript compilés et les hooks explicites. Il enregistre leur manifeste d'empreintes, fournit un plan de clés v1 dans l'ordre observé du builder et vérifie l'absence de changement de code ou de snapshot pendant l'essai. Le bundle CLI construit dans ce lot a l'empreinte `bacd157688b3f1fb4b64a30673b69cba9b8fded4782481897da4f929d63ada60`. Environnement : Node `24.8.0`, V8 `13.6.233.10-node.27`, darwin/arm64.

Les comparaisons portent sur la liste des fichiers et leurs octets contre un témoin généré sans cache dans cette campagne : **5 371 fichiers**, dont 2 686 PHP, 2 684 CoreFn et un manifeste Composer.

| Essai | États restaurés | Modules générés | États sauvegardés et comparés après relecture | Écritures PHP | Fichiers identiques au témoin |
| --- | ---: | ---: | ---: | ---: | ---: |
| Témoin sans cache | 0 | 2 684 | 0 | 2 686 | référence |
| Remplissage + round-trip | 0 | 2 684 | 2 684 | 0 | 5 371 |
| Restauration totale | 2 684 | 0 | 0 | 0 | 5 371 |
| Préfixe restauré, suffixe réoptimisé | 835 | 1 849 | 0 | 0 | 5 371 |
| Restauration de sorties endommagées | 2 684 | 0 | 0 | 3 | 5 371 |

Le préfixe mixte s'arrête après `Core.Message.Command.Command`, la dépendance partagée de M0. Les modules suivants sont forcés en miss, sans mutation des entrées : ils doivent retrouver les implementations/directives/arités fournies par le préfixe restauré. Cet essai contrôle la restauration d'état indépendamment de l'invalidation, déjà couverte par les clés v1.

La restauration totale préserve les **2 686 mtime PHP**. Le dernier essai supprime `Core.Message.Command.Command/index.php` et altère `Inter.Api.Main/main.mod.php` ainsi que `Inter.Api.Main/main.bundle.php` : exactement ces trois PHP sont réécrits. Chaque invocation publie encore **2 684 `.purmeta`**, même après suppression du dossier et sur 100 % de hits.

Le stockage des deux formes PHP et de l'état PBO complet occupe **570 735 280 octets (544,3 Mio)** dans 2 684 entrées. Ces essais valident la génération et les états ; l'exécution PHP est vérifiée par les fixtures. Ils ne constituent pas une campagne de performance du cache activé dans la CLI.

## Reproduction et artefacts

Après `npm run build`, depuis le dépôt :

```sh
node audit/2026-10-01/module-state/verify-b8x.mjs \
  /chemin/vers/b8x \
  /chemin/vers/b8x/run/bak/php/output \
  /chemin/vers/dossier-neuf
```

Le [résumé JSON](results.json) est conservé dans le dépôt. Le snapshot, le témoin, les entrées de cache, les journaux, les listes ordonnées de modules et les manifestes d'entrée/code sont dans `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b1-state-Pv4KZOCd`.
