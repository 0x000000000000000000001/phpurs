# B4 — comptage AST complet et limite de réécriture effective

Date : 3 octobre 2026. Deuxième micro-étape B4.

## Implémentation

- `Phpurs.AstMetrics` remplace le match partiel de `Main`. Le `Foldable
  BackendSyntax` de PBO énumère tous les enfants immédiats ; une liste de travail
  parcourue en récursion terminale traite ensuite les descendants. Les littéraux
  composites, valeurs d'updates, conditions/résultats/défaut des branches,
  opérandes primitifs et wrappers `Typed`/`TypeApp` sont inclus.
- Un nœud signifie une occurrence d'expression. Les types, noms, binders et
  conteneurs de groupes ne sont pas ajoutés au total ; les positions partageant
  le même objet sont toutes comptées. La mesure reste conditionnée à `--verbose`
  et à une optimisation fraîche, dans l'intervalle `diagnostics`.
- `Phpurs.RewriteLimit` valide une seule valeur effective avant les lectures
  d'entrées et la transmet au builder et au contexte des clés persistantes.
  Défaut **10 000**, entiers décimaux positifs jusqu'à **2 147 483 647**, formes
  séparée et `=`, premier argument prioritaire, arguments Spago groupés acceptés.
  Les écritures numériques équivalentes partagent les clés ; changer la valeur
  partitionne tout le plan de réutilisation.
- La sémantique du garde PBO reste celle de l'optimiseur : un binding qui demande
  trop de passes échoue, avec le nom du binding. Le chemin chunké des grandes
  expressions reste décrit par son contrat existant. Cette étape ne choisit
  aucune nouvelle limite de production.

Contrats détaillés : [diagnostics](../../../docs/optimizer-diagnostics.md),
[profilage](../../../docs/build-profile.md) et [cache](../../../docs/cache.md).

## Régressions

`npm run build` réussit sans avertissement ni erreur. La suite
`npm run test:codegen` passe ses **75 contrôles**. Après le dernier ajustement de
validation lexicale et l'enrichissement des assertions, les suites concernées
ont été rejouées avec succès : `ast-metrics.mjs`, `rewrite-limit.mjs` et
`build-profile.mjs`.

Couverture ajoutée :

- tous les constructeurs `BackendSyntax`, opérateurs unaires/binaires et effets
  new/read/write, expressions imbriquées, groupes récursifs, partage d'objets ;
- profondeur de 100 000 paires de wrappers, soit **200 001 nœuds**, et largeur de
  100 000 éléments, champs de records, arguments d'application ou paires de
  branches ; aucun débordement de pile ;
- compte CLI exact **8, 16, 10** pour les trois modules de la fixture typée, dans
  les trois modes d'émission, au lieu de **2, 2, 1** auparavant ;
- options invalides refusées avant lecture CoreFn et mutation des sorties/cache,
  y compris les caractères de contrôle ; bornes `Int` et précédence ;
- défaut implicite/explicite, zéros initiaux, limites 1 et 10 001, arguments
  groupés, retour à une ancienne clé et `--no-cache` ; PHP, mtimes et exécutions
  modulaires/bundlées identiques, résultat **43** ;
- un vrai binding `Limit.run` distribuant une application sur une conditionnelle
  échoue à la limite 1, même après un build chaud. La limite 100 réussit, puis
  obtient quatre hits. Les deux branches exécutées en PHP donnent **43, 2**.

Sur l'exécutable témoin figé, trois régressions échouent intentionnellement :
comptage AST partiel, absence d'invalidation à 10 001, et succès incorrect à la
limite 1 après un build chaud. Les journaux `before-ast-regression.log` et
`before-regression.log` sont conservés à côté des artefacts de validation.

## Protocole b8x

Le script [verify.py](verify.py) fige les deux exécutables et copie le corpus
immuable en préservant les chemins FFI voisins. Il lance trois processus frais :
témoin avec défaut implicite, nouveau défaut implicite, nouvelle limite 10 001.
Tous utilisent Node v24.8.0, `GOPURS_JOBS=1`, le budget PBO par défaut et :

```text
--main Inter.Api.Main --bundle --no-cache --verbose --profile-build
```

Le contrôle crée les sorties ; leurs mtimes PHP sont ensuite fixées à une date
ancienne. Les deux variantes comparent toutes les empreintes et conservent ces
mtimes. Une sonde externe vérifie les lectures CoreFn, publications `.purmeta`
et écritures PHP réelles. Les comptes complets par module doivent être identiques
entre les deux limites suffisantes. Le corpus source et sa copie sont vérifiés
après les trois builds.

Les trois builds de la comparaison finale sont terminés et vérifiés. Les **5 372
fichiers** sont identiques au témoin, dont les **2 686 PHP**. Les deux variantes
conservent toutes les mtimes PHP, avec **zéro écriture PHP réelle**. Les **2 684
modules** sont tous optimisés et comptés ; le corpus d'entrée reste inchangé.

| Exécutable / limite | Nœuds affichés | Diagnostic, s | Processus, s | Écritures PHP |
| --- | ---: | ---: | ---: | ---: |
| Témoin / défaut 10 000 | 24 356 | 0,169 | 50,186 | 2 686 |
| Final / défaut 10 000 | 1 514 089 | 0,423 | 48,858 | 0 |
| Final / explicite 10 001 | 1 514 089 | 0,443 | 49,162 | 0 |

Le compte augmente dans **2 316 modules**. L'ancien match arrêtait notamment
le parcours dès un wrapper `Typed`, en plus de ses autres omissions. Les plus
gros comptes complets sont `Inter.Cli.Logic.Registry` (**88 430**),
`Inter.Api.Registry` (**60 649**) et `Data.CodePoint.Unicode.Internal`
(**47 492**). Le détail des chronos, RSS et classements est dans
[results.json](results.json) ; les comptes par module sont conservés dans les
artefacts bruts.

Les chronos sont des observations diagnostiques uniques, pas une mesure de gain
de compilation ou de surcoût du compteur. Le témoin crée le PHP tandis que les
variantes le comparent ; la machine est partagée. La comparaison de limites plus
basses sur les modules coûteux, avec leur runtime, reste une étape B4 ultérieure.

## Identités et reproduction

- HEAD PHPurs au début : `34ef98e1cacb89c6d171f55ec8dd067e3a3f0fc4`, avec les
  changements B4 dans le checkout. Le commit
  `8d8295456101a5eea908161bd8d02112275ff703` a été observé en fin de validation :
  il contient le code, les tests, les contrats et le script de vérification B4.
  Le présent bilan, ses résultats et la clôture du point dans `todo.md` le suivent.
- HEAD PBO : `c9386b4d572503bb7b6d1ce9547ec30dcded2920`, checkout propre.
- SHA-256 CLI témoin : `b324346395e449a70a93ba2a94e671dedb6963c39624d3ed40f10f859f22ca95`.
- SHA-256 CLI final : `6d3c9172d475bcc3f943ec3b779eb64f3bef840947ba5cc86634b71858c056a7`.
- Manifeste des 2 839 fichiers d'entrée :
  `9e159394a364c95af49aa1d66cdcc7d3c66ec410acbaf70b19f96bfda98e7372`.

```sh
python3 audit/2026-10-03/ast-rewrite-limit/verify.py \
  --snapshot /chemin/vers/snapshot \
  --before /chemin/vers/before.mjs \
  --after bin/phpurs.js \
  --artifacts /chemin/vers/un-dossier-vide
```

Artefacts locaux :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b4-limits-_5l7xt2n/validation`.
Ils contiennent les exécutables figés, profils complets, logs, comptes par module,
sondes I/O, empreintes de référence et arbre de sortie vérifié. Un premier essai
complet précède le resserrement lexical du parseur ; ses logs restent dans
`../preliminary`, et les résultats publiés ici portent sur le binaire final.
