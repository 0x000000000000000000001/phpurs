# B2 — Comparer les budgets LRU

## Protocole et critères

La campagne compare **16, 64 et 128 Mio**, avec **trois répétitions par budget**. Le budget historique de 64 Mio sert de témoin. Une compilation préalable prépare les sorties et les compare à la référence B1 ; elle est exclue des échantillons. L'ordre des neuf builds mesurés tourne entre les réglages : `16, 64, 128` ; `64, 128, 16` ; `128, 16, 64`. Chaque budget occupe ainsi chacune des trois positions.

Les critères sont fixés avant les mesures : une hausse au-delà de 64 Mio est recommandée si elle réduit les relectures, apporte au moins **3 % de gain médian sur le temps backend** et reste sous **+10 % de RSS maximal médian** par rapport au témoin. Ces seuils servent à décider d'un changement utile dans cette campagne courte ; les valeurs min/médiane/max permettent aussi de voir le recouvrement des mesures. Le petit budget est évalué pour son éventuelle économie de mémoire face au coût des relectures.

Le corpus b8x figé contient 2 684 modules dans 2 839 fichiers, d'empreinte `9e159394a364c95af49aa1d66cdcc7d3c66ec410acbaf70b19f96bfda98e7372`. Le bundle est celui du [lot de configuration](../purmeta-budget/report.md), SHA-256 `185ebc9926d25ca7bc89e71d0e14955d2284da7a66dda13a6eac497b8a86c2a6`. L'exécutable local ayant été régénéré au format Spago entre les deux lots, la campagne sélectionne explicitement cette copie autonome déjà validée.

Environnement : Node 24.8.0, macOS arm64, Mac16,7, 14 cœurs, 48 Gio de RAM physique. Chaque processus utilise `GOPURS_JOBS=1`, `--main Inter.Api.Main --bundle --no-cache --profile-purmeta` et son budget explicite. Les processus sont exécutés successivement, avec des sorties déjà présentes et des mtime PHP fixés. Le RSS est le high-water mark OS du processus complet, incluant la finalisation ; le budget porte sur les tailles sérialisées de la LRU.

Le [script d'audit](../purmeta-budget/verify.py) accepte désormais `--budgets`, `--repetitions` et `--backend`. Il confronte les compteurs PBO à deux probes externes, vérifie les limites après trim et l'identité des compteurs logiques entre répétitions d'un même budget. Chaque build doit reproduire la liste et les octets des sorties B1, conserver les mtime PHP, effectuer zéro écriture PHP et optimiser les modules dans le même ordre. Le corpus source est revérifié en fin d'essai. Les charges système avant/après chaque invocation sont conservées avec les durées et les profils.

```sh
python3 audit/2026-10-02/purmeta-budget/verify.py \
  --snapshot /chemin/vers/snapshot \
  --reference-output /chemin/vers/sorties-B1 \
  --backend /chemin/vers/backend-valide.mjs \
  --artifacts /chemin/vers/dossier-neuf \
  --budgets 16 64 128 --repetitions 3
```

## Résultats

Les compteurs logiques sont identiques entre les trois répétitions d'un même budget. Les trois politiques effectuent chacune 55 506 requêtes, dont 1 622 rejets d'appartenance au build courant, et 2 684 écritures `.purmeta`. Aucune erreur de lecture/écriture ni aucun fichier courant manquant.

| Mesure par build | 16 Mio | 64 Mio, témoin | 128 Mio |
| --- | ---: | ---: | ---: |
| Hits RAM | 50 953 | 53 167 | 53 785 |
| Relectures/désérialisations | 2 931 | 717 | **99** |
| Volume lu (Mio) | 314,98 | 80,13 | **10,34** |
| Taux de hit RAM, hors rejets | 94,56 % | 98,67 % | **99,82 %** |
| Évictions | 5 231 | 2 443 | 1 291 |
| Pic sérialisé intramodule (Mio) | 46,38 | 84,07 | 132,12 |
| Maximum après trim (octets) | 16 777 178 | 67 108 740 | 134 217 726 |
| Backend médian (s) | 51,216 | 52,191 | **47,040** |
| Backend min–max (s) | 48,993–59,998 | 49,650–57,331 | 46,769–48,225 |
| Optimisation/émission médiane (s) | 46,782 | 47,380 | 42,560 |
| Désérialisation médiane (ms) | 2 825,882 | 715,047 | **98,389** |
| Sérialisation médiane (ms) | 1 630,742 | 1 700,600 | 1 598,346 |
| Pic RSS médian (Mio) | 3 377,6 | 3 361,8 | **3 495,5** |
| Pic RSS min–max (Mio) | 3 344,9–3 393,0 | 3 141,0–3 466,2 | 3 037,6–3 509,7 |

Chaque invocation mesurée conserve **5 372 fichiers identiques** à la référence B1, les **2 686 mtime PHP** et effectue **zéro écriture PHP**. Les 2 684 modules sont réoptimisés dans le même ordre. Les limites sont respectées après les trims ; les dépassements intramodule sont conformes au contrat. Le corpus source final est identique.

## Décision

**128 Mio est retenu comme réglage opt-in pour la compilation complète de ce corpus b8x.** Par rapport à 64 Mio :

- **86,19 % de relectures en moins**, et **87,10 % d'octets lus en moins** ;
- backend médian inférieur de **9,87 %**, au-delà du seuil fixé à 3 % ;
- pic RSS médian supérieur de **3,98 %**, soit environ **134 Mio**, sous le seuil de 10 %.

Le budget de 16 Mio ne procure pas d'économie de RSS médian dans cette campagne et entraîne environ quatre fois plus de relectures que 64 Mio. Son avantage en taille de cache retenue ne se traduit donc pas en baisse du RSS du processus.

Le réglage retenu utilise l'option déjà validée :

```sh
phpurs --main Inter.Api.Main --bundle --purmeta-cache-mib 128
```

Le défaut de l'API PBO et de PHPurs reste à 64 Mio ; l'adoption de 128 Mio est explicite et ciblée sur cette charge de compilation. `--purmeta-cache-mib 64` fournit le repli. Pour mesurer à nouveau les optimisations complètes, ajouter `--no-cache --profile-purmeta`.

### Portée des durées

Les échantillons ont été pris sur une machine partagée : la charge moyenne à une minute varie d'environ 5 à 12,3 sur 14 cœurs, et les durées d'écriture fluctuent. Les trois essais à 128 Mio sont ici plus courts que chacun des essais à 64 Mio, mais **l'écart de 9,87 % décrit cette campagne**, avec trois répétitions seulement. Le témoin de préparation, hors série, dure 47,405 s. La baisse déterministe des relectures et celle des durées de désérialisation sont les constats les plus directement reliés au budget ; la totalité du gain backend ne peut pas être attribuée à ces seules opérations.

## Validation et artefacts

Le [résumé conservé](results.json) contient les neuf échantillons dans l'ordre réel, toutes les durées de phases, les charges système, les profils logiques par budget et les min/médianes/max. Ses valeurs sont vérifiées contre le résultat brut de l'audit. L'exécutable utilisé est la copie exacte de celui qui avait passé les 61 contrôles PHPurs et 20 contrôles PBO du lot de configuration.

Les journaux, profils complets, probes, listes de modules, entrées copiées et exécutable sont conservés dans `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b2-budget-comparison-e6pbHyyN`. Le script vérifie l'ensemble des invariants avant de marquer le résultat `verified: true`.

Étape B2 suivante : expliciter le répertoire, le format/version et l'invalidation des `.purmeta` avant toute réutilisation inter-builds.
