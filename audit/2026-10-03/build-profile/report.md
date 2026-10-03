# B4 — première étape : profil des phases et logs optionnels

## Changement

`--profile-build` ajoute un relevé JSON v1 sur stderr, avec durée totale, métriques
par phase et par module. `--verbose` active les messages de génération et le
parcours du compteur AST historique. Les cinq résumés de durée et les erreurs
restent disponibles dans une invocation ordinaire.

Les phases distinguent E/S CoreFn, décodage/empreinte, tri, capture FFI, hooks du
cache persistant, optimisation PBO, traduction PHP, impression, comparaison/
écriture conditionnelle, entrées, collecte Composer et nettoyage. Le
[contrat](../../../docs/build-profile.md) précise les frontières, notamment :

- le créneau d'optimisation du builder séquentiel commence après le miss d'état
  et se termine à l'entrée de `onCodegenModule`, avant PHP ;
- traduction et impression utilisent des thunks pour que le travail pur strict
  soit effectué après le premier relevé d'horloge ;
- une mesure `php.write` comprend encodage, comparaison et suivi de sortie ;
  elle peut réussir sans aucune écriture réelle ;
- les durées détaillées ne couvrent pas toute la coordination ni la publication
  `.purmeta` post-codegen ; les lectures parallèles peuvent se chevaucher ;
- chaque collecteur est propre à son invocation, y compris lors d'imbrications ;
  les exceptions et annulations ferment les créneaux et conservent le préfixe
  mesuré. Aucun état du compilateur n'est stocké dans ces relevés.

Les interfaces ordinaires `loadInputs`, `renderModuleState` et
`publishModuleState` restent disponibles ; les appels instrumentés utilisent le
même calcul et la même publication. Les scripts d'audit qui comptent les appels
codegen à partir de leurs messages demandent désormais `--verbose` explicitement.

## Régressions

- Le nouveau contrôle des logs échoue sur l'exécutable précédent avec
  `module diagnostics should require --verbose`, puis passe sur la version
  instrumentée.
- Sept nouveaux contrôles couvrent les trois modes d'émission, les arguments
  groupés, la combinaison avec `--profile-purmeta`, les hits complets/mixtes,
  l'identité des fichiers et l'exécution PHP.
- Échecs de lecture/écriture : rapport partiel, phase fautive marquée et erreur
  d'origine propagée. Les tests vérifient également l'identité d'une exception
  pure, l'attente d'une action Aff, les profils imbriqués et l'annulation d'une
  optimisation sans callback codegen.
- `npm run build` : zéro avertissement/erreur. `npm run test:codegen` : **68
  contrôles réussis**. Les scripts Python/JavaScript modifiés passent leurs
  vérifications de syntaxe et le diff passe la vérification des espaces.

## Corpus et protocole b8x

2 839 entrées figées, dont 2 684 CoreFn. Empreinte du manifeste d'entrée :
`9e159394a364c95af49aa1d66cdcc7d3c66ec410acbaf70b19f96bfda98e7372`.
Node v24.8.0, `GOPURS_JOBS=1`, budget PBO par défaut, mêmes chemins et options
`--main Inter.Api.Main --bundle`. Chaque build utilise un nouveau processus.

Exécutables autonomes figés :

- avant : `2ad2c77295d04dbe99ddd8b541f1d29131127cb9634b2465e5aacf4a94f5fd2c` ;
- après : `b324346395e449a70a93ba2a94e671dedb6963c39624d3ed40f10f859f22ca95`.

Le binaire courant a été reconstruit une dernière fois et garde l'empreinte
mesurée. HEADs observés au début du lot : PHPurs
`34ef98e1cacb89c6d171f55ec8dd067e3a3f0fc4`, PBO
`c9386b4d572503bb7b6d1ce9547ec30dcded2920`.

Le [script](verify.py) exécute un témoin avant sans cache, la version instrumentée
sans cache, une préparation du cache B1 sans profil puis un hit complet profilé.
Une sonde de fichiers extérieure confronte les lectures CoreFn et les écritures
PHP aux métriques. Les hashes des entrées sont revérifiés dans le snapshot et
dans sa copie après tous les essais.

La limite de temps du premier lancement a interrompu la préparation B1 après
les deux premiers essais réussis. Le processus interrompu était arrêté ; les
2 686 hashes de sorties suivies et leurs mtime fixes ont été vérifiés avant
reprise. Le cache partiel a été supprimé, puis préparation et hit ont été
terminés avec `--resume`, sans répéter les deux contrôles achevés. Le script
conserve désormais aussi le manifeste de sorties attendu dans son checkpoint.

## Résultats

Les quatre builds donnent **5 372 fichiers identiques** : CoreFn, PHP généré,
manifeste Composer et manifeste de sorties. Après le témoin initial, les
**2 686 fichiers PHP conservent leur mtime**, et la sonde observe **zéro écriture
PHP**. Les entrées figées restent intactes. L'exécution PHP est vérifiée par les
fixtures ; l'application b8x est vérifiée ici par comparaison de ses sorties.

| Phase détaillée | Build complet profilé | Hit B1 complet profilé |
| --- | ---: | ---: |
| E/S CoreFn, sondes incluses | 454,660 ms | 465,544 ms |
| Décodage TAST + empreinte | 3 666,953 ms | 3 678,667 ms |
| Tri des modules | 20,438 ms | 19,881 ms |
| Chargement d'états B1 | 1,776 ms, hooks désactivés | 4 850,066 ms |
| Optimisation | **27 982,737 ms**, 2 684 appels | **0 appel** |
| Traduction PHP | **6 492,732 ms**, 2 684 appels | **0 appel** |
| Impression | **3 358,775 ms**, 2 686 appels | 0,240 ms, deux entrées |
| Tentatives mkdir | 912,468 ms | 1 102,578 ms |
| Comparaison/écriture PHP | 1 098,394 ms, 2 686 appels | 901,097 ms, 2 686 appels |
| Reachability des entrées | 59,750 ms | 54,411 ms |
| Collecte Composer | 2,308 ms | 2,052 ms |
| Nettoyage final | 6,103 ms | 4,997 ms |

Les 2 684 décodages correspondent aux lectures réellement recensées par la
sonde. Les 2 686 impressions du build complet sont les 2 684 modules plus les
deux entrées ; sur hit, seules ces deux entrées sont imprimées. Le profil chaud
est obtenu après une préparation sans `--profile-build` : **2 684 hits, zéro miss
et zéro store**, confirmant l'indépendance des options de diagnostic vis-à-vis
des clés persistantes. Les 2 684 `.purmeta` sont toujours publiés dans chaque
processus, y compris sur hit B1.

Les modules les plus coûteux de ce relevé sont notamment :

| Module | Optimisation | Traduction | Impression |
| --- | ---: | ---: | ---: |
| `Inter.Cli.Logic.Registry` | 577,252 ms | 172,072 ms | 368,700 ms |
| `Inter.Api.Registry` | 570,023 ms | 110,593 ms | 260,097 ms |
| `Core.Feat.Review.Message.Query.SearchArticles.Projection.Projection` | 343,096 ms | 101,466 ms | 91,745 ms |

Ce sont des relevés diagnostiques uniques sur machine partagée, avec effets du
GC et de l'ordonnanceur inclus. Le témoin créait les PHP et journalisait tous les
modules ; les essais suivants réutilisaient les fichiers identiques. Les durées
globales et RSS conservés ne servent donc pas à annoncer un gain ou un surcoût
du profiler. La première étape B4 est close ; comptage AST complet et prise en
compte de `--rewrite-limit` constituent la micro-étape suivante.

Résultats compacts : [results.json](results.json). Journaux, profils par module,
sondes et copies figées :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b4-profile-s_nfukck/validation`.
