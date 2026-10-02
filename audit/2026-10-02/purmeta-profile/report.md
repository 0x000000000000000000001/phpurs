# B2 — Mesurer le cache mémoire PBO existant

Base PHPurs : `a7b414aff7578a779446b61df9fb7fe6c0a2ef14`. Base PBO : `0f41544464ec0f42e6cb0dd77b206852813f904f`. Le bundle instrumenté porte la SHA-256 `5a177451d97eef432fb87ef87503fb7aa768ba1a37368f0f9e50263f2c3498ec`.

## Constat et instrumentation

Le constat initial B2 était antérieur au code présent : `Builder` appelle déjà `trimPurmetaCache` après chaque publication, avec une LRU plafonnée à **64 Mio de tailles sérialisées**, puis conservée sous forme décodée. Le budget s'applique aux frontières de modules ; il peut être dépassé pendant leur traitement. `beginPurmetaBuild` vide la RAM et réinitialise l'appartenance au build courant. Le clear explicite conserve l'accès aux fichiers publiés dans ce build.

`Cache.js` expose désormais un profil opt-in via `setPurmetaStatsEnabled` et `readPurmetaStatsJson`. Les compteurs distinguent requêtes rejetées par l'appartenance, hits/misses RAM, lectures disque réussies/absentes/erronées, octets lus/écrits, sérialisations/désérialisations, temps d'E/S et pics de taille du cache. Les durées de sérialisation incluent les parcours des constructeurs PureScript, pas seulement les appels V8. La mémoire rapporte deux échantillons RSS et le high-water mark du processus.

La CLI PHPurs active ce profil avec `--profile-purmeta`, produit un unique JSON sur stderr après optimisation/émission et le désactive même en cas d'échec. Ce flag est observationnel et ne partitionne pas les clés B1. `--no-cache` force l'optimisation complète nécessaire pour observer les consultations PBO ; les hits d'états PHPurs sont suivis séparément. Le [contrat du profil](../../../docs/purmeta-profile.md) définit chaque compteur, unité et périmètre.

## Validation

- `npm run build` réussit sans avertissement.
- `npm run test:codegen` passe **56 contrôles**. La nouvelle fixture compare les octets produits avec/sans profilage, confronte les compteurs aux lectures/écritures réellement interceptées, vérifie les hits B1 avec arguments groupés et exécute les PHP modulaire/bundle (`43`). Un échec d'émission rapporte le préfixe PBO effectué et conserve l'erreur d'origine.
- **16 contrôles PBO** passent : trois diagnostics, six LRU, trois de cycle de build et quatre de lookup d'implémentations. Ils couvrent activation/désactivation sans éviction, remise à zéro entre builds, rejets des anciennes métadonnées, pics/dépassements/évictions, remplacements et erreurs de lecture/décodage/écriture. La fixture historique du hit builder fournit maintenant son champ `decls`, requis par l'analyse des globals privés.
- L'import inutilisé `foldM` du builder a été retiré lors de sa recompilation.

Les tests PBO utilisent exactement les modules compilés par le build PHPurs :

```sh
node ../../purescript-backend-optimizer-phpurs/test/purmeta-stats.mjs output
node ../../purescript-backend-optimizer-phpurs/test/purmeta-lru.mjs output
node ../../purescript-backend-optimizer-phpurs/test/purmeta-build-cache.mjs output
node ../../purescript-backend-optimizer-phpurs/test/implementation-lookup.mjs output
```

## Protocole b8x

[`measure.py`](measure.py) copie le corpus figé du [lot d'état](../../2026-10-01/module-state/report.md), soit 2 839 fichiers dont 2 684 CoreFn. Le manifeste source a pour empreinte `9e159394a364c95af49aa1d66cdcc7d3c66ec410acbaf70b19f96bfda98e7372`. L'exécutable autonome est copié dans les artefacts avant les mesures.

Toutes les invocations utilisent `--main Inter.Api.Main --bundle` et `GOPURS_JOBS=1`. Une première compilation sans profilage prépare les sorties et les compare au témoin B1 précédent. Les essais suivants partagent ces sorties, avec mtime PHP fixé : un témoin sans profilage, trois compilations complètes profilées avec `--no-cache`, puis remplissage et hits complets du cache PHPurs avec profilage PBO.

Chaque état compare les **listes et octets** de ses sorties au témoin B1. Le probe extérieur recense indépendamment les appels `fs.readFileSync`/`fs.writeFileSync` aux `.purmeta`, leurs octets et le RSS maximal à la sortie du processus. Le script confronte ces valeurs au JSON PBO, vérifie les identités de comptage, l'identité des compteurs logiques entre répétitions, les mtime PHP et le corpus source final. Les timings/RSS sont conservés séparément des compteurs déterministes.

Une première tentative a échoué pendant la préparation sur `ENOSPC`, avant toute mesure profilée valide. Elle est exclue des résultats. Après libération de caches et `.purmeta` jetables issus des essais précédents, la campagne repart dans un dossier neuf `retry/`. Le journal de cet échec reste dans le dossier parent.

## Résultats de la compilation complète

Les trois répétitions ont exactement les mêmes compteurs logiques, confirmés par les traces E/S du témoin sans profilage :

| Compteur | Valeur par build |
| --- | ---: |
| Modules optimisés/générés | 2 684 |
| Requêtes PBO après memoization locale | 55 506 |
| Requêtes hors appartenance au build courant | 1 622 |
| Hits RAM | **53 167** |
| Misses RAM = lectures/désérialisations disque réussies | **717** |
| Taux de hit, hors rejets d'appartenance | **98,67 %** |
| Octets lus | **84 025 923 — 80,13 Mio** |
| Sérialisations/écritures | 2 684 |
| Octets écrits | **179 596 288 — 171,28 Mio** |
| Fichiers absents / erreurs | 0 / 0 |
| Trims / clears explicites | 2 684 / 0 |
| Évictions LRU | 2 443 |
| Pic de taille sérialisée retenue, pendant un module | **88 156 228 octets — 84,07 Mio** |
| Maximum juste après un trim | 67 108 740 octets — moins de 64 Mio |
| Taille retenue à la fin | 66 240 929 octets — 63,17 Mio, 958 modules |

Le budget concerne une estimation sérialisée du contenu retenu, pas la mémoire totale du compilateur. Le dépassement observé pendant un module est conforme à la politique d'élagage aux frontières. Les entrées relues peuvent être évincées à nouveau : le compteur d'évictions décrit des événements, pas des noms de modules uniques.

Les six rebuilds mesurés reproduisent chacun les **5 372 fichiers** du témoin B1, octet par octet, conservent les **2 686 mtime PHP** et n'écrivent aucun PHP. Le corpus figé est revérifié identique en fin d'essai.

## Durées et mémoire

Node 24.8.0, darwin/arm64, sans `NODE_OPTIONS`. Les sorties PHP existent déjà pour le témoin et les trois répétitions profilées. Les valeurs de sérialisation/E/S sont des durées cumulées du build ; le RSS de ce tableau est le high-water mark OS relevé à la sortie du processus, incluant la finalisation.

| Invocation | Backend (s) | Pic RSS (Mio) |
| --- | ---: | ---: |
| Témoin sans profilage | 46,552 | 3 369,5 |
| Profilage complet 1 | 47,895 | 3 269,8 |
| Profilage complet 2 | 48,800 | 3 280,8 |
| Profilage complet 3 | 47,985 | 3 356,2 |
| **Médiane des trois profils** | **47,985** | **3 280,8** |
| Remplissage du cache PHPurs, profilé | 52,654 | 3 750,8 |
| Hits complets du cache PHPurs, profilés | 12,866 | 3 183,8 |

Médianes des durées cumulées du chemin PBO sur les trois compilations complètes :

| Opération | Durée (ms) |
| --- | ---: |
| Sérialisation complète | **1 615,459** |
| Désérialisation complète | **710,085** |
| Lectures synchrones des `.purmeta` | 64,310 |
| Écritures synchrones des `.purmeta` | 611,967 |

Le taux de hit est déjà élevé avec la politique actuelle. Les relectures/desérialisations ont néanmoins un coût mesuré, que les prochains budgets pourront comparer à la mémoire retenue. Le témoin de profilage n'a été exécuté qu'une fois : cette série ne suffit pas à estimer précisément le surcoût de l'instrumentation sur une machine partagée. Aucun gain runtime PHP n'est attendu de cette étape de mesure.

## Hits B1 et encodage des métadonnées

Le remplissage du cache PHPurs conserve les mêmes compteurs PBO que la compilation complète. Le build suivant restaure les **2 684 modules** : zéro codegen, zéro requête/lecture PBO, mais encore **2 684 sérialisations et écritures `.purmeta`**, avec 1 347,370 ms de sérialisation dans cet essai.

Ces écritures représentent **179 596 165 octets**, soit 123 octets de moins que le chemin frais. L'assertion initiale du script qui exigeait l'égalité des volumes frais/restaurés était trop forte pour le format V8 non canonique. Une compilation supplémentaire hors série a reproduit les compteurs du chemin frais et ses sorties, puis [`compare-purmeta.mjs`](compare-purmeta.mjs) a comparé les 2 684 payloads : **2 669 sont identiques en octets**, et les **15 autres ont des graphes décodés équivalents**, avec mêmes clés, tags, valeurs numériques (`Object.is`) et partage d'objets dans les deux sens. La liste et les tailles sont conservées dans le [résumé JSON](results.json).

Le script reproductible conserve désormais les métadonnées fraîches avant les hits et effectue ce contrôle de graphes. La vérification supplémentaire, dont une première tentative a atteint le délai d'exécution, reste hors des trois échantillons de durée. Les résultats originaux sont conservés, puis finalisés après cette vérification ciblée. Le bundle final et sa copie exécutée sont identiques ; `git diff --check` réussit dans les deux dépôts.

## Reproduction et artefacts

```sh
python3 audit/2026-10-02/purmeta-profile/measure.py \
  --snapshot /chemin/vers/snapshot \
  --reference-output /chemin/vers/sorties-B1 \
  --artifacts /chemin/vers/dossier-neuf \
  --repetitions 3
```

Sans `--reference-output`, le script conserve un témoin local après préparation. Les journaux, compteurs détaillés, listes de modules, entrées copiées et exécutable figé sont dans `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b2-profile-6rYnvR1h/retry`.
