# B1 — Clé de cache de module, v1

Base PHPurs : `415487f7838e9db8ffd1a876b3bc8c2558c18f7d`. PBO : `0f41544464ec0f42e6cb0dd77b206852813f904f`. Le lot précédent de [mesure M0](../build-scenarios/report.md) fournit les mutations et les différences PHP de référence.

## Contrat livré

`Phpurs.CacheKey` expose un type opaque `Fingerprint`, le hachage des octets bruts et `planKeys`, un calcul pur de clés pour les modules dans l'ordre réel du builder. Le [contrat détaillé](../../../docs/cache.md) fixe l'encodage SHA-256 par tuples étiquetés, versionné `phpurs/module-cache-key` v1.

La clé couvre les octets CoreFn complets, l'identité de l'exécutable PHPurs/PBO, les versions d'outillage et d'hôte, les options effectives, les directives initiales, les états FFI et les clés des dépendances déjà traitées. Les états « aucune déclaration foreign », « source absente » et « fichier sélectionné » sont distincts ; un fichier sélectionné apporte son chemin et l'empreinte de ses octets.

La lecture du builder a confirmé une contrainte supplémentaire : ses directives et ses globals privés s'accumulent dans l'ordre des modules. `Main` accumule aussi les arités PHP. Un chaînage des clés précédentes couvre donc cet état, même pour les modules non importés. Une modification invalide toute la suite du flux ; l'ajout, la suppression ou le réordonnancement de modules change le contexte de tout le graphe.

Les noms de module ambigus et les dépendances présentes mais situées plus tard dans le flux sont refusés par `Left`, sans plan partiel. Les dépendances absentes du corpus, dont les modules `Prim.*`, ont un marqueur explicite. La restauration de l'état complet sur cache hit est l'étape B1 suivante ; le calcul de clés est aujourd'hui exercé par les régressions et cet audit.

## Régressions et vérifications

Sept nouveaux contrôles dans `tests/codegen/cache-key.mjs` couvrent :

1. Déterminisme, indépendance de l'ordre des propriétés des records, encodage sans ambiguïté des champs et indépendance des appels successifs.
2. Invalidation par chaque champ de versions/outillage/hôte, directives et options effectives, y compris l'absence d'autoloader versus sa valeur par défaut explicite, qui produit un PHP différent.
3. Changement d'une dépendance avec un CoreFn consommateur identique, propagation transitive et état d'un prédécesseur non importé.
4. Ajout, suppression, changement d'ordre et apparition d'un import jusque-là absent.
5. Refus des doublons, noms vides, dépendances vers l'avant, cycles et répertoire de travail implicite.
6. Octets CoreFn, annotations de type, éditions de même taille avec ancien mtime, séquences UTF-8 distinctes au décodage identique et exécutable modifié sans changement de version déclarée.
7. Résolution FFI réelle : absence, fichier vide, modification à mtime conservé, apparition d'un fichier adjacent prioritaire à contenu identique, suppression et retour au fallback.

`npm run build` réussit sans avertissement PureScript ; `npm run test:codegen` passe les **31 contrôles**. Les fixtures PHP exécutables font partie de cette suite. Le bundle du driver conserve l'empreinte `9d0f3b08f089e5d07565ac357ca7764c0b2a453a9e3373cf3db1ee3637af13ca` : le nouveau calcul est une API compilée indépendante, appelée par les validations. `git diff --check` réussit.

## Vérification sur le corpus b8x

[`verify-b8x.mjs`](verify-b8x.mjs) reprend l'ordre des 2 684 modules de M0, les octets réels de leurs CoreFn et les trois variantes déjà validées contre des générations fraîches. Il résout les FFI avec `PackagePaths` et hache la chaîne de directives réellement fournie par PBO. Les chemins FFI et le contexte représentent le même projet logique dans les trois états.

Le corpus est accepté sans dépendance vers l'avant. Il contient 2 385 modules sans foreign, 165 résolutions absentes et 134 sources PHP sélectionnées. Chaque module dont le PHP change dans M0 doit avoir une clé différente ; le témoin identique doit retrouver le même plan complet, y compris après les autres calculs.

| État | Clés identiques | Clés différentes | Modules PHP différents dans M0 | Tous couverts |
| --- | ---: | ---: | ---: | --- |
| Aucun changement | **2 684** | **0** | 0 | oui |
| Feuille modifiée | 1 851 | 833 | 1 | oui |
| Dépendance modifiée | 834 | 1 850 | 30 | oui |

Les différences correspondent exactement au suffixe commençant au module modifié : index zéro-based 1 851 pour `Inter.Api.Main`, 834 pour `Core.Message.Command.Command`. Les 29 consommateurs de la constante modifiée sont tous couverts. Les bundles portent le nombre de fichiers PHP différents à 2 et 31 dans M0 ; ils sont finalisés depuis les contributions de modules.

Cette première politique privilégie une identité complète du flux de compilation. Les 833 et 1 850 invalidations sont plus larges que les différences de PHP observées : affiner cette portée demandera d'exposer et d'identifier plus précisément l'état partagé. Ces nombres sont des comparaisons de clés, pas des cache hits ni des gains de compilation mesurés.

## Reproduction et artefacts

Après `npm run build`, depuis le dépôt :

```sh
node audit/2026-10-01/cache-key/verify-b8x.mjs \
  /chemin/vers/snapshot \
  /chemin/vers/artefacts-M0 \
  /chemin/vers/dossier-neuf
```

Le snapshot utilisé est `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b3-jzxtnkx0/snapshot`, les artefacts M0 sont `phpurs-b1-scenarios-VbMW8uFA` sous le même dossier temporaire. Les clés de référence, les listes d'invalidations et la comparaison de ce lot sont dans `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b1-key-6RTR5tto`. Le [résumé JSON](comparison.json) est conservé dans le dépôt.
