# B2 — Configurer le budget LRU depuis PHPurs

Base PHPurs : `7398caf734d3ee2b28434bb6d002f9cd0ff73380`. Base PBO : `0f41544464ec0f42e6cb0dd77b206852813f904f`, avec l'instrumentation du lot précédent.

## Contrat implémenté

`--purmeta-cache-mib N` règle le budget des tailles sérialisées retenues par la LRU PBO. L'unité est le Mio entier, converti en octets sans troncature 32 bits ; les valeurs invalides sont rejetées avant lecture des entrées. La forme `--purmeta-cache-mib=N` et les arguments groupés sont acceptés. La première occurrence l'emporte, conformément au parseur CLI partagé.

Le budget par défaut reste **64 Mio** pour les appelants JavaScript. L'absence d'option conserve la politique active ; `--purmeta-cache-mib 64` offre un repli explicite. **0** vide la RAM à chaque frontière de module tout en conservant la réutilisation intramodule et le repli disque sur les implémentations publiées dans le build courant.

PBO expose `setPurmetaCacheBudgetBytes :: Number -> Effect Number`, qui valide le nouveau budget et retourne le précédent. Le changement n'évince rien immédiatement ; les trims existants appliquent la limite aux frontières de modules. `beginPurmetaBuild` réinitialise contenus et appartenance, mais conserve le réglage.

`Phpurs.PurmetaBudget` encadre le builder avec `Aff.bracket` : succès, erreur et annulation restaurent le budget précédent, puis élaguent les éventuelles données excédentaires conservées sous une limite supérieure. Le profil est émis avant cette restauration et indique le budget réellement testé. Le réglage est indépendant du profilage et des clés du cache persistant PHPurs. Le [contrat détaillé](../../../docs/purmeta-profile.md#configuring-the-budget) précise unités et portée.

## Validation automatisée

- `npm run build` réussit sans avertissement.
- `npm run test:codegen` passe **61 contrôles**, dont cinq nouveaux : parsing et plage exacte, restauration dans le même processus après succès/erreur/annulation, budgets 0/1/16/64/128 Mio, indépendance du cache persistant et erreurs CLI précoces.
- Les PHP modulaire et bundle donnent `43` pour tous les budgets testés. Une modification FFI dans un build mixte, avec dépendances restaurées puis évincées à budget nul, donne `44`, comme le témoin frais. Les octets produits et les mtime PHP sont contrôlés ; les E/S `.purmeta` sont confrontées à un probe extérieur.
- **20 contrôles PBO** passent : quatre nouveaux de configuration, trois de profilage, six LRU, trois de cycle de build et quatre de lookup. Ils vérifient budget nul, recency, frontières de modules, tailles supérieures au budget, comptage des remplacements, appartenance au build courant, limites invalides et absence de troncature des grands compteurs.

Commandes depuis le dépôt PHPurs, après compilation :

```sh
npm run build
npm run test:codegen
node ../../purescript-backend-optimizer-phpurs/test/purmeta-budget.mjs output
node ../../purescript-backend-optimizer-phpurs/test/purmeta-stats.mjs output
node ../../purescript-backend-optimizer-phpurs/test/purmeta-lru.mjs output
node ../../purescript-backend-optimizer-phpurs/test/purmeta-build-cache.mjs output
node ../../purescript-backend-optimizer-phpurs/test/implementation-lookup.mjs output
```

## Protocole b8x

[`verify.py`](verify.py) copie le corpus figé utilisé par le [lot de mesure](../purmeta-profile/report.md), l'exécutable autonome et les probes d'E/S dans un dossier neuf. Il compile avec `--main Inter.Api.Main --bundle --no-cache --profile-purmeta` et `GOPURS_JOBS=1` : d'abord sans option de budget, puis avec **0**, puis avec **64 Mio explicites**.

Le témoin par défaut est comparé aux listes et octets de la référence B1. Chaque rebuild doit conserver ces sorties, les mtime PHP fixés et le même ordre de modules. Les compteurs PBO sont confrontés aux E/S réelles ; le budget nul doit laisser zéro entrée après chaque trim, et le budget explicite de 64 Mio doit reproduire tous les compteurs logiques du défaut. Le corpus source est revérifié en fin d'essai. Les durées/RSS d'une invocation par réglage sont conservées dans le JSON comme observations de validation ; le choix d'un budget reste l'étape B2 suivante.

```sh
python3 audit/2026-10-02/purmeta-budget/verify.py \
  --snapshot /chemin/vers/snapshot \
  --reference-output /chemin/vers/sorties-B1 \
  --artifacts /chemin/vers/dossier-neuf
```

Sans `--reference-output`, le script conserve un témoin local après la première compilation.

## Résultats b8x

L'essai valide les **2 684 modules** du corpus de 2 839 fichiers, d'empreinte `9e159394a364c95af49aa1d66cdcc7d3c66ec410acbaf70b19f96bfda98e7372`. Le bundle exécuté, identique au `bin/phpurs.js` validé, porte la SHA-256 `185ebc9926d25ca7bc89e71d0e14955d2284da7a66dda13a6eac497b8a86c2a6`.

Les trois compilations produisent les **5 372 fichiers identiques** au témoin B1. Après fixation des mtime, les deux rebuilds conservent les **2 686 mtime PHP** et n'écrivent aucun PHP. Les 55 506 requêtes PBO, dont 1 622 rejets d'appartenance, restent identiques ; chaque build publie 2 684 `.purmeta` sans erreur. Les compteurs logiques du budget explicite de 64 Mio sont exactement ceux du défaut.

| Compteur | Défaut 64 Mio | 0 Mio | 64 Mio explicites |
| --- | ---: | ---: | ---: |
| Hits RAM | 53 167 | 29 375 | 53 167 |
| Relectures/désérialisations | 717 | 24 509 | 717 |
| Octets lus | 84 025 923 | 2 266 787 775 | 84 025 923 |
| Octets écrits | 179 596 288 | 182 141 712 | 179 596 288 |
| Pic de tailles sérialisées, pendant un module | 88 156 228 | 35 963 244 | 88 156 228 |
| Maximum après trim | 67 108 740 | **0** | 67 108 740 |
| Entrées finales | 958 | **0** | 958 |
| Évictions | 2 443 | 27 193 | 2 443 |

Le budget nul conserve donc bien la réutilisation intramodule : 29 375 hits RAM, même avec zéro entrée à chaque frontière. Les volumes d'écriture des métadonnées sont mesurés pour chaque politique ; l'identité des PHP est vérifiée octet par octet.

Observations d'une seule invocation par configuration, Node 24.8.0, sans `NODE_OPTIONS` :

| Invocation | Backend (s) | Désérialisation (s) | Pic RSS processus (Mio) |
| --- | ---: | ---: | ---: |
| Défaut, préparation des sorties | 49,778 | 0,730 | 3 459,3 |
| Budget 0, sorties déjà présentes | 71,038 | 19,190 | 3 696,5 |
| Budget 64 explicite, sorties déjà présentes | 50,863 | 0,728 | 3 512,5 |

Ces valeurs confirment que le budget des tailles sérialisées et le RSS global sont deux mesures distinctes. La prochaine étape comparera des petits et moyens budgets sur plusieurs répétitions pour choisir un compromis.

Le [résumé JSON](results.json) conserve les mesures complètes. Le corpus source final est identique et `git diff --check` réussit dans les deux dépôts. Entrées copiées, exécutable figé, journaux, listes de modules et probes sont conservés dans `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b2-budget-tbW01Udo`.
