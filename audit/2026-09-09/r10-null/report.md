# RBTree : arbre vide nullable et tests de nullité

Le prototype sur copies ramène **RBTree de 171–173 à 138–140 ms** et le **total de 190–193 à 157–159 ms**, soit **16,67–18,30 % de temps total en moins**. Le pic mémoire du processus complet passe de **34 à 26 MiB**. Les valeurs, les anciennes racines et les observations publiques testées sont préservées.

**Ce gain n'est pas encore intégré au générateur.** Les copies partent du PHP actuel avec R0/R8/R9/R10/R11. Les 301 modules actifs et les 22 fichiers sources du backend restent inchangés. Aucun build, commit ou changement de README ; seuls cet audit et le todo sont ajoutés/modifiés.

## Mesures

PHP 8.5.4, JIT 1255 actif, tampon 128 MiB, Xdebug absent, cache de fichiers OPcache désactivé. Processus neufs, successifs, ordre direct puis inversé. Le runner utilise les actions générées et le protocole existant de `Bench.purs` : trois warm-ups, puis minimum de dix, avec le chronomètre dans le programme. Les compteurs et contrôles de structure sont exécutés séparément des timings.

| RBTree isolé | Ordre direct | Ordre inversé | Pic mémoire |
| --- | ---: | ---: | ---: |
| Témoin actuel | 177,814 ms | 176,011 ms | 22 MiB |
| `E = null`, conserver les tests `instanceof T` | 159,539 ms | 159,863 ms | 16 MiB |
| Ajouter les tests `T !== null` | 139,848 ms | 144,292 ms | 16 MiB |

La suppression des objets vides et de leurs tests de classe économise ici **16–18 ms** ; remplacer ensuite les tests de nœuds ajoute **16–20 ms**. Ces deltas viennent de cette expérience actuelle, pas de l'ancien prototype après R9. Voir [bench-results.json](bench-results.json).

| Suite complète | Témoin 1 | Prototype 1 | Prototype 2 | Témoin 2 |
| --- | ---: | ---: | ---: | ---: |
| RBTree | 171,217 ms | 139,551 ms | 138,132 ms | 173,131 ms |
| Total des 14 tests | 190,468 ms | 158,709 ms | 157,297 ms | 192,522 ms |
| Pic mémoire réservé | 34 MiB | 26 MiB | 26 MiB | 34 MiB |

Par paire, RBTree gagne **18,49–20,22 %**, le total **16,67–18,30 %**. Les 14 résultats attendus passent à chaque run. L'essentiel de la baisse totale correspond à RBTree ; les autres modules ne changent pas. Voir [full-results.json](full-results.json) et les quatre journaux complets.

Le README officiel du checkout principal `altbak.pub` donne actuellement **169,936 ms pour RBTree / 188,94 ms au total**. Le README du worktree garde le relevé R11 précédent, **176,562 / 196,11 ms**. Les témoins contemporains sont cohérents avec cet intervalle ; les pourcentages utilisent les paires contemporaines.

La dernière colonne native historique indique **123,096 ms pour RBTree / 200,45 ms au total**. Le prototype reste environ **12–13 % plus lent que le RBTree natif**, avec un écart de **15–16 ms**. Aucun nouveau run natif n'est effectué ici. Un total compilé inférieur au total natif historique ne prouve pas une supériorité générale : les écarts diffèrent fortement selon le benchmark.

## Transformation et domaine

La région privée R10 déjà sélectionnée calcule `depth (buildTree dummy E)` et rend un entier. Le patch diagnostique ne touche que les références qualifiées aux deux classes privées de cette région. Quatre sites `new E()` deviennent `null`, trois tests `instanceof E` deviennent `=== null`, puis 59 tests `instanceof T` deviennent `!== null` dans la dernière variante.

L'enveloppe Effect, la boucle de 100 000 insertions décroissantes, les rotations, le calcul de profondeur et les fonctions publiques ne changent pas. Les déclarations des classes privées restent présentes, y compris celle de `E`, désormais inutilisée sur ce chemin.

Le TAST décrit **`Tree = E | T Color Tree Int Tree`**, sans variable de type, avec exactement un constructeur vide et un constructeur contenant des champs. Les enfants gauche et droit sont de type `Tree`. Dans cette région fermée, une valeur de ce type est donc soit `null`, soit un objet `T`. L'objet `T` reste matérialisé, même lorsque ses deux enfants valent `null` : le nœud et l'arbre vide ne se confondent pas. Voir [tast-layout.json](tast-layout.json).

Ce contrôle vérifie le layout réel de RBTree et réutilise le périmètre fermé déjà établi par R10. **Le patch de PHP figé n'est pas une nouvelle preuve générique du compilateur.** Une intégration doit transporter cette propriété dans le même AST typé que les sites réécrits ; elle ne doit pas convertir des valeurs inconnues ou publiques en se fondant seulement sur la forme du PHP.

## Comptage et exactitude

Les compteurs sans JIT, dans des copies distinctes, donnent par action :

| Construction/appel | Témoin | `E = null` | Tests de nullité |
| --- | ---: | ---: | ---: |
| Objets privés `E` | 200 001 | 0 | 0 |
| Nœuds privés `T` | 2 583 932 | 2 583 932 | 2 583 932 |
| Appels privés à `ins` | 2 283 976 | 2 283 976 | 2 283 976 |
| Appels privés à `balance` après R9 | 0 | 0 | 0 |
| Constructions publiques sur ce chemin | 0 | 0 | 0 |

Les trois singletons publics d'initialisation du module sont exclus du comptage. Aucun nœud persistant, appel récursif ou partie du travail demandé n'est supprimé. Voir [counts.json](counts.json).

Les trois variantes passent les mêmes contrôles :

- **1 285 insertions** : ordres croissant, décroissant, permutation déterministe de clés négatives/positives et doublons, plus arbre vide ;
- comparaison exacte des structures canoniques privées et publiques après chaque insertion, vérification de l'ordre des clés, de leur nombre, de la racine noire, des hauteurs noires et de l'absence de rouges adjacents ;
- profondeur générée comparée à celle calculée par le validateur ;
- **1 290 paires d'anciennes racines** conservées puis revérifiées ;
- sérialisations publiques identiques entre variantes, couleurs publiques toujours objets `R/B`, arbre vide public toujours objet `E`, application partielle publique toujours `Closure`, contrôle PHP du champ clé toujours actif.

Les empreintes de structures et de sérialisations sont égales dans [validation.json](validation.json). Les trois actions complètes rendent également `22` avant les timings.

[state.json](state.json) vérifie que les 302 fichiers PHP actifs, dont 301 modules, restent identiques au snapshot initial. Seul `Test.RBTree/index.php` diffère dans les deux copies modifiées, et son préfixe contenant toutes les classes et fonctions publiques reste identique. Les 22 fichiers sources du générateur sont également inchangés.

## Reproduction et prochaine étape

Depuis le checkout PHPurs :

```sh
python3 audit/2026-09-09/r10-null/prepare.py
python3 audit/2026-09-09/r10-null/validate.py
python3 audit/2026-09-09/r10-null/count.py
python3 audit/2026-09-09/r10-null/measure.py bench
python3 audit/2026-09-09/r10-null/measure.py full baseline null-checks
python3 audit/2026-09-09/r10-null/check-state.py
```

`prepare.py` exige l'empreinte exacte du PHP étudié et crée des snapshots physiques, sous `raw/` ignoré par Git. L'application chronométrée charge bien les modules de sa variante.

La prochaine petite étape est **l'intégration de la représentation nullable dans les régions privées R10**, avec preuve du layout et du type de chaque test. Garder le constructeur contenant des champs sous forme d'objet et les frontières publiques intactes ; prévoir des tests de refus pour les layouts ambigus, types ouverts/inconnus et valeurs qui sortent de la région. Rejouer les invariants, racines conservées et régressions de frontières, régénérer par `bin/php/run -c`, puis mesurer avec la nouvelle transformation activée et désactivée avant de compter ce gain comme livré.
