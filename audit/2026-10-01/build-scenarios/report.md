# B1 — Trois états de recompilation du backend

Référence PHPurs : checkout propre au commit `3471ae4c13de854018a7ff8a0819a84de8162a3f`, après le [lot des écritures conditionnelles](../write-if-changed/report.md). PBO : `0f41544464ec0f42e6cb0dd77b206852813f904f`. Le bundle mesuré porte l'empreinte SHA-256 `9d0f3b08f089e5d07565ac357ca7764c0b2a453a9e3373cf3db1ee3637af13ca`.

## Protocole

Le corpus est le snapshot b8x figé utilisé par les lots B3 et B1 précédents : 2 684 CoreFn typés, avec les fichiers PHP FFI et les manifestes Composer à leurs chemins relatifs. La campagne appelle directement le backend avec `--main Inter.Api.Main --bundle`, dans des copies isolées. Les durées couvrent la lecture TAST, l'optimisation, la traduction, l'impression et les E/S du backend. Elles excluent la compilation du compilateur hôte, la compilation PureScript du programme, l'installation Composer et l'exécution de l'application.

Le manifeste des 2 827 fichiers d'entrée porte l'empreinte `610a324f397510d9e13529ffdc309dece84b40b961bb0714e65d471a104ae5df`. Les CoreFn non modifiés représentent 213 935 062 octets. Environnement : Node `24.8.0`, macOS `26.1`, arm64, sans `NODE_OPTIONS` hérité.

Le script [`measure.py`](measure.py) définit trois états :

| État | Entrée modifiée |
| --- | --- |
| `unchanged` | Reprise des mêmes entrées et sorties. |
| `leaf` | Littéral de `Inter.Api.Main.handle` : `Not found.` → `Not found (M0 leaf).` |
| `dependency` | Constante `Core.Message.Command.Command.defaultMaxConcurrencyRetries` : `50` → `51`. |

« Feuille » désigne ici un module applicatif sans importeur dans le graphe du corpus, donc sans consommateur à invalider. La dépendance choisie expose une constante dont 29 modules utilisent une référence qualifiée dans leur CoreFn.

Les modifications portent directement sur un littéral du TAST gelé. Le script exige un binding et un littéral uniques, enregistre leur chemin JSON et leurs empreintes, puis vérifie qu'exactement un CoreFn change. Tous les autres octets d'entrée, y compris les annotations et les tables de types, sont conservés. Ce protocole mesure les états du backend ; il n'exerce pas l'invalidation incrémentale du compilateur PureScript en amont.

Pour chaque état, une génération dans un arbre sans sorties PHP ni `.purmeta` fournit le témoin. La génération fraîche non modifiée est aussi comparée au témoin B1 précédent. Chaque rebuild repart d'une copie complète de cette génération non modifiée, avec ses PHP et ses `.purmeta`, puis reçoit seulement la mutation de son scénario.

Trois tours reprennent chacun les trois états, dans l'ordre tournant `unchanged / leaf / dependency`, puis `leaf / dependency / unchanged`, puis `dependency / unchanged / leaf`. Chaque invocation utilise un nouveau processus Node. Les dates des PHP sont fixées à une valeur ancienne avant chaque essai. Après le build, le script vérifie :

- la même liste de fichiers et les mêmes octets que le témoin fraîchement généré pour cet état ;
- une liste d'écritures PHP égale à la liste des contenus qui diffèrent de l'état initial ;
- une modification du mtime uniquement pour ces fichiers ;
- le succès de la lecture des 2 684 CoreFn et l'absence d'erreurs de décodage, de directives ou de lecture purmeta ;
- l'unicité des modules passés par le callback de génération ;
- la préservation du snapshot d'origine en fin de campagne.

## Instrumentation

[`count-io.mjs`](count-io.mjs) est chargé par `node --import` avant le bundle. Les exports ESM natifs de `fs` sont synchronisés après interception des lectures asynchrones et des écritures synchrones/asynchrones. Les compteurs enregistrent les fichiers et octets CoreFn lus, les sorties PHP relues pour comparaison, les écritures PHP, `.purmeta` et Composer, ainsi que le pic RSS du processus Node via `process.resourceUsage().maxRSS` (KiB).

Les lignes `Generating PHP code for …` recensent les appels à `onCodegenModule`, après passage par la branche d'optimisation de PBO. Le nombre de modules retraités est donc distinct du nombre de fichiers réécrits. Chaque liste ordonnée complète est conservée dans un fichier `.modules.json` ; sa SHA-256 permet de comparer les essais. Les compteurs détaillés sont dans les `.io.json`, les durées par phase dans les journaux et `results.json`. Le temps `backend total` contient les phases ; le temps de processus inclut aussi le démarrage de Node.

La lecture des modules est fixée à `GOPURS_JOBS=1`. Les options Node héritées sont consignées dans les résultats. Les médianes, minimums et maximums portent sur trois processus par état ; ils décrivent cette campagne sur machine partagée.

## Résultats des neuf rebuilds

Les douze générations, témoins compris, terminent avec code 0. Chaque rebuild reproduit **les 5 371 fichiers de son témoin frais**, liste et octets compris. Les PHP de contenu identique conservent leur mtime. La génération fraîche non modifiée reproduit également les 5 371 fichiers de la référence B1 précédente.

| Compteur par rebuild | Aucun changement | Feuille modifiée | Dépendance modifiée |
| --- | ---: | ---: | ---: |
| CoreFn modifiés | 0 | 1 | 1 |
| Modules lus et retraités | **2 684** | **2 684** | **2 684** |
| Modules évités | 0 | 0 | 0 |
| Écritures PHP | **0** | **2** | **31** |
| Octets PHP écrits | 0 | 66 581 110 | 67 343 160 |
| mtime PHP préservés | 2 686 | 2 684 | 2 655 |
| Écritures `.purmeta` | 2 684 | 2 684 | 2 684 |
| Octets `.purmeta` écrits | 179 596 288 | 179 596 298 | 179 596 288 |
| Sorties PHP relues | 2 686 | 2 686 | 2 686 |
| Octets PHP relus | 135 282 480 | 135 282 480 | 135 282 480 |

Ces compteurs sont identiques dans les trois répétitions de chaque état. Chaque invocation réécrit aussi un manifeste Composer de 191 octets. La liste ordonnée des modules retraités est la même dans les douze processus, avec SHA-256 `a70f580e789716eeb9721e84d3724b431d91f861b0f632264da17bc22f5d56bb`.

| Mesure | Aucun changement | Feuille modifiée | Dépendance modifiée |
| --- | ---: | ---: | ---: |
| Backend, médiane en secondes | **50,681** | **52,952** | **63,584** |
| Backend, min–max en secondes | 49,278–52,561 | 46,149–79,152 | 50,121–67,742 |
| Processus, médiane en secondes | 51,160 | 53,397 | 64,105 |
| Pic RSS, médiane en MiB | 3 556,3 | 3 569,9 | 3 562,6 |
| Pic RSS, min–max en MiB | 3 297,4–3 574,4 | 3 169,8–3 598,5 | 3 502,4–3 614,7 |

La dispersion est importante : par exemple, la lecture TAST du premier essai « dépendance » prend 13 741 ms, contre 4 493–4 629 ms dans les deux autres. Les écarts de durée entre états ne permettent pas d'isoler un surcoût causal de la mutation. Le constat reproductible est le retraitement intégral des modules et des `.purmeta`, malgré les écritures PHP limitées aux sorties différentes.

Les [mesures conservées dans le dépôt](measurements.json) contiennent chaque échantillon, les phases, les compteurs et les statistiques. Les témoins frais ont duré respectivement 72 510, 59 007 et 47 962 ms ; ils servent au contrôle des sorties. Les validations de cette campagne portent sur la génération et la comparaison différentielle du PHP.

## Propagation observée dans les témoins frais

La mutation de la feuille change exactement `Inter.Api.Main/index.php` et son `main.bundle.php`.

Le module de dépendance compte **68 importeurs directs** et **179 importeurs transitifs** dans le graphe CoreFn. Parmi eux, **29 modules** contiennent une référence qualifiée à la constante modifiée. Leurs 29 `index.php`, celui de la dépendance et le bundle changent : **31 fichiers PHP**. Ces ensembles sont conservés dans `graph.json` et `results.json`.

Par exemple, le CoreFn de `Core.Feat.Membership.Message.Command.ChangeUserEmail.Command` est strictement identique avant/après ; son dictionnaire PHP contient pourtant cette différence :

```diff
- "maxRetries" => 50
+ "maxRetries" => 51
```

La valeur de la dépendance a donc été incorporée au PHP du consommateur pendant l'optimisation. Ce cas fournit un témoin concret pour la prochaine étape B1 : la clé du consommateur devra couvrir les dépendances de son résultat optimisé, en plus de son propre CoreFn. Les listes d'importeurs et les listes de PHP différents sont conservées séparément pour guider cette définition.

## Validation du lot

- `python3 audit/2026-10-01/build-scenarios/measure.py --help` et `node --check audit/2026-10-01/build-scenarios/count-io.mjs` réussissent.
- La campagne complète réussit : trois témoins frais, neuf rebuilds comparés, contrôle des écritures et mtime, snapshot d'origine préservé.
- Les valeurs de `measurements.json` sont vérifiées contre les résultats bruts de la campagne.
- `git diff --check` réussit.

## Reproduction

Depuis la racine du dépôt, avec le backend construit et le snapshot disponible :

```sh
python3 audit/2026-10-01/build-scenarios/measure.py \
  --snapshot /chemin/vers/snapshot \
  --artifacts /chemin/vers/dossier-neuf \
  --reference-output /chemin/vers/sortie-b1-validee \
  --repetitions 3
```

`--snapshot` désigne la racine contenant `b8x/` et `phpurs/`. Le dossier d'artefacts doit être neuf ou vide ; les copies de travail et les mutations y sont confinées. Le backend par défaut est `bin/phpurs.js` du dépôt ; `--backend` permet d'en sélectionner explicitement une autre version. Le contrôle de la référence précédente est facultatif, les contrôles frais propres à chaque état sont systématiques.

La campagne du 1er octobre 2026 utilise le snapshot `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b3-jzxtnkx0/snapshot` et la référence `phpurs-b1-emission-UhRlI7AZ/after/b8x/output` sous le même dossier temporaire. Ses artefacts sont conservés dans `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b1-scenarios-VbMW8uFA`.
