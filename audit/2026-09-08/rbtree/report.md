# Profil RBTree — 8 septembre 2026

**Premier chantier : réduire les variables locales générées dans les branches exclusives de `balance`.** Une variante qui ne change que leurs noms passe de **644,6–677,4 ms à 282,6–283,5 ms**, soit environ **56–58 % de temps en moins**. Ce potentiel est mesuré sur une copie diagnostique du PHP ; il reste à implémenter et valider la transformation générique dans PHPurs.

## Mesure conservée

- Action originale `Test.RBTree.act` : 100 000 insertions décroissantes, puis profondeur de l'arbre, résultat `"22"` vérifié à chaque appel.
- Chrono original de `Bench.purs` : trois échauffements, dix passages mesurés, minimum. Build et Composer sont exclus de ce chrono.
- PHP 8.5.4, OPcache CLI actif, JIT `1255`, buffer 128 Mio. Xdebug absent des comparaisons de performance ; statut JIT actif enregistré à chaque processus.
- Deux passages par variante, ordre inversé au second tour. Les chronos internes sont fiables pour ce travail ; leur remplacement n'est pas un préalable.
- Machine partagée : la référence varie de 5 %. Les petits écarts des autres variantes ne suffisent pas pour conclure à un gain stable.

## Ce que le profil montre

Un échantillonnage natif de cinq secondes, après échauffement, observe **1 809 / 4 128 échantillons du thread principal (43,8 %) dans `zend_free_compiled_variables`**, en tête de pile. Il s'agit du nettoyage des variables d'une fonction, distinct du collecteur de cycles.

La fonction `balance` du [PHP original conservé](generated-RBTree.php#L248) contient **447 lignes et 249 variables PHP distinctes**, dont **223 noms de binders**. PBO duplique des branches du pattern matching ; PHPurs attribue ensuite un suffixe frais à chaque liaison, y compris dans des branches exclusives. Les 21 sites de rotation et 19 sites de fallback sont des occurrences statiques, pas des exécutions.

Le relevé Xdebug, limité à une action avec JIT désactivé, précise les volumes :

| Fonction / constructeur | Appels | Part du temps propre instrumenté |
| --- | ---: | ---: |
| `ins` | 2 283 976 | 63,4 % |
| `balance` | 2 183 976 | 24,1 % |
| constructeur `T` | 2 583 932 | 3,3 % |
| `insert` | 100 000 | 2,8 % |
| `buildTree` | 1 | 2,7 % |
| `depth` | 200 001 | 2,4 % |
| constructeurs `R` + `B` + `E` | 699 938 | 0,5 % |

Les comptes incluent trois initialisations de singletons pour `R/B/E`. Les pourcentages viennent d'un passage instrumenté d'environ 5,98 s : **ils ne sont pas la répartition du temps sous JIT**. Les coûts d'allocation/nettoyage peuvent être imputés au code appelant ; le temps du seul corps d'un constructeur ne mesure pas le coût complet d'un objet. Les temps inclusifs récursifs se recouvrent et ne doivent pas être additionnés.

Le chemin chaud utilise déjà des appels natifs saturés. Il ne crée pas une closure par insertion. Les wrappers d'arité et quelques copies TCO inutiles existent, mais ne sont pas la première piste mesurée.

Sur l'exécution native originale de 15 actions, le collecteur de cycles consomme **156 ms sur 10,79 s (1,45 %)**, avec **30 passages et zéro cycle collecté**. Cela ne compte pas tout le travail de gestion mémoire : allocations, décrémentations de références et destructions ordinaires restent distinctes.

## Variantes isolées sous JIT

Toutes repartent du même PHP original. Elles ne sont pas cumulées.

| Variante | Meilleurs temps de deux processus (ms) | Pic mémoire PHP réservé (Mio) |
| --- | --- | ---: |
| Référence | 677,428 ; 644,645 | 30 |
| Réutilisation des binders de `balance` | **283,527 ; 282,590** | 30 |
| Réutilisation des singletons `R/B/E` | 635,443 ; 637,330 | **16** |
| Suppression des wrappers d'arité sur cinq fonctions chaudes | 645,528 ; 645,855 | 30 |
| Suppression des copies TCO mortes de `ins/depth` | 644,971 ; 642,249 | 30 |
| Collecteur de cycles désactivé | 670,611 ; 664,883 | 30 |

La variante principale réutilise le nom correspondant au niveau lexical des seuls binders de `balance` : **223 noms deviennent 7 ; 249 variables deviennent 33**. Les branches, appels et expressions de construction restent identiques. Le second échantillonnage natif contient 4 233 échantillons du thread principal et aucune occurrence du symbole `zend_free_compiled_variables`. La réduction des locaux peut aussi modifier les choix du JIT ; le profil ne permet pas d'attribuer tout le gain à une seule routine C.

Les singletons réduisent nettement la mémoire PHP réservée (mesurée par `memory_get_peak_usage(true)`, pas le RSS). Leur petit avantage temporel demande une mesure supplémentaire après la première optimisation. Les autres variantes restent dans l'ordre de grandeur de la variation de la référence.

Validation des copies : syntaxe PHP valide ; même résultat `22` sur les actions mesurées ; mêmes empreintes de l'arbre complet après chaque insertion sur **1 285 insertions** couvrant ordres croissant, décroissant, mélangé, clés négatives et doublons, plus l'arbre vide. Cette vérification concerne les variantes RBTree ; elle ne prouve pas qu'un renommage textuel est sûr pour toutes les captures et portées PureScript.

Un premier chrono de référence de 1 111 ms a été écarté : la copie venait d'être écrite et `opcache.file_update_protection` empêchait sa mise en cache. Le buffer JIT n'avait consommé que 3 592 octets, contre 43 184 sur les références retenues. Le relevé exclu est conservé séparément. Le script de reproduction désactive désormais cette protection pour ses seules copies diagnostiques.

## Baby steps retenus

1. Ajouter une fixture de branches imbriquées avec masquage de noms et utilisation d'une valeur extérieure après la jonction.
2. Couvrir les closures échappantes, captures récursives par référence et captures conservées au fil d'une boucle.
3. Séparer l'allocation des noms de binders des identifiants de labels : les labels doivent rester uniques dans la fonction.
4. Réutiliser des emplacements seulement entre branches prouvées exclusives, sans conflit de durée de vie ou de capture. Commencer par les binders anonymes ; aucune règle nommant `Test.RBTree`.
5. Régénérer avec PHPurs, rejouer les fixtures et les 14 sorties, puis comparer avec le même `Bench.purs`.

Origine dans le générateur : `src/Phpurs/CodeGen.purs`, `Let` lignes 377–402 et `Branch` lignes 521–564. Dans le worktree PBO : `src/PureScript/Backend/Optimizer/Convert.purs`, construction des deux sous-arbres lignes 987–998 et répartition des wildcards lignes 1048–1058. La réduction du decision tree et les retours directs restent des étapes distinctes ultérieures.

## Fichiers et reproduction

- [Résultats complets](ablation-results.json), journaux `bench-*.log`, [validation structurelle](validation.json).
- [Résumé Xdebug et arêtes d'appels](callgrind-summary.json), [résumé natif](native-summary.json), statuts JIT dans les journaux.
- [Empreintes des entrées](input-hashes.json), [PHP original](generated-RBTree.php), [variantes reproductibles](ablate.py), [runner](runner.php).
- `raw/` est ignoré par Git : profil Callgrind d'environ 515 Mio, rapports natifs, copies des variantes et cache OPcache. Les résumés restent versionnables.

Depuis ce dossier : `python3 ablate.py` reproduit les comparaisons séquentielles et leurs vérifications. `python3 native-sample.py` profile le code original ; `python3 native-sample.py reuse-binders` profile la variante après sa création. L'échantillonnage macOS nécessite de pouvoir examiner le seul processus enfant lancé par le script.

Le PHP original d'altbak.pub-phpurs est inchangé et son empreinte correspond au snapshot. Aucun changement de performance n'a été appliqué à PHPurs ou PBO pendant ce profilage. Le backlog `todo.md` place désormais ce chantier en R0.
