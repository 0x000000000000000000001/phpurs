# Représentation nullable privée : intégration R10

La transformation est activée dans PHPurs. `altbak.pub-phpurs/bin/php/run -c` réussit et les sorties actives correspondent au générateur intégré. Dans les comparaisons alternées, **RBTree passe de 177–179 à 146–148 ms**, le **total de 196–199 à 166–168 ms**, soit **15,39–15,71 % de temps total en moins**. Le pic mémoire réservé passe de **34 à 26 MiB**.

## Mesures après régénération

PHP 8.5.4, JIT 1255 actif avec tampon 128 MiB, Xdebug absent, cache de fichiers OPcache désactivé. Quatre processus neufs et successifs, ordre témoin/intégration puis intégration/témoin. Aucun build, test ou compteur lancé simultanément par cette tâche. La machine reste partagée. Le runner conserve les actions générées et `Bench.purs` : trois warm-ups, puis minimum de dix, avec l'horloge dans le programme.

| Suite complète | Témoin 1 | Intégration 1 | Intégration 2 | Témoin 2 |
| --- | ---: | ---: | ---: | ---: |
| RBTree | 179,300 ms | 148,072 ms | 146,033 ms | 176,845 ms |
| Total des 14 benchmarks | 198,500 ms | 167,945 ms | 165,547 ms | 196,398 ms |
| Pic mémoire | 34 MiB | 26 MiB | 26 MiB | 34 MiB |

Le gain RBTree est de **17,42 %**, soit **30,8–31,2 ms** par action dans les paires. Les 14 sorties sont exactes dans les quatre processus. Résultats complets : [full-results.json](full-results.json).

Le témoin conserve R0/R8/R9/R10 couleurs/R11 et désactive uniquement la sélection et l'abaissement des constructeurs nullable. Il retrouve exactement les **302 fichiers PHP précédents**, dont les 301 modules et le RBTree SHA256 `58d6145d3a0e15fc99792555bab37e73079591ce53dafe1226294a321c74fcc9`. Seul `Test.RBTree` diffère entre les variantes. Les sorties actives correspondent intégralement à la variante intégrée : voir [state.json](state.json).

La préparation garde le dossier de travail et les `modulePath` TAST de la compilation normale ; seules les destinations PHP sont différentes. Déplacer ces métadonnées avait changé la génération de `Control.Monad.List.Trans` dans une première préparation. Le protocole final supprime cette différence et retrouve le snapshot antérieur dans sa totalité.

La commande complète `bin/php/run -c` donne un premier total de **155,68 ms** ; elle valide la reconstruction. Les pourcentages ci-dessus utilisent les témoins contemporains et ne sélectionnent pas ce run plus rapide. Le [prototype sur copies](../r10-null/report.md) donnait 157–159 ms contre 190–193 ms dans ses propres conditions ; ces mesures restent distinctes de l'intégration.

Le README principal d'`altbak.pub` donne **188,94 ms / RBTree 169,936 ms** ; celui du worktree gardait **196,11 ms / 176,562 ms**. Le worktree est actualisé avec le second run intégré, en conservant les références précédentes. La dernière colonne native, indiquée WIP dans le README principal, reste une référence historique : **RBTree 123,096 ms / total 200,45 ms**. Aucun natif n'est remesuré ici. Le RBTree intégré reste environ **19–20 %** au-dessus de ce repère historique ; comparer les totaux seuls ne permet pas de conclure à une supériorité générale.

## Preuve et génération

`EnumRegions` conserve sa preuve transitive sur l'AST typé optimisé : appels locaux directs et saturés, types monomorphes, entrées libres et résultat scalaires, absence d'ADT transmis aux FFI/callbacks ou conservé dans un objet public. Les champs inconnus, fonctions, records et types ouverts restent refusés. Les budgets de preuve et de copies R10 sont inchangés.

`NullableConstructors.layout` sélectionne un layout sans variable de type, avec exactement **deux constructeurs : un vide et un contenant des champs**. Les layouts comportant plusieurs alternatives vides ou plusieurs alternatives avec champs ne sont pas convertis en nullable. Les enums restent pris en charge par leur représentation entière existante.

Une région ne nécessite plus de contenir un enum pour bénéficier de cette optimisation : un arbre ou une chaîne monomorphe seul suffit. Après validation de tous les champs et usages, `EnumRegions` émet une table des **noms frais des constructeurs privés**. Les types ADT et leurs layouts restent dans le TAST ; aucun opérateur booléen fictif ou valeur `undefined` n'est introduit pour représenter `null`.

Après TCO et inlining, l'abaissement structurel dans l'AST PHP utilise exclusivement cette table :

- construction du constructeur vide privé → `null` ;
- test de ce constructeur → `=== null` ;
- test de son alternative avec champs → `!== null`.

Le constructeur contenant des champs reste un objet, même si ses champs sont nullable. Ainsi, `Present Empty` reste distinct de `Missing`. Le nom qualifié de chaque classe relie les nœuds PHP aux constructeurs privés prouvés ; aucune reconnaissance de noms de benchmark ou substitution de texte PHP.

Le parcours inclut les closures Effect contenant les expressions d'entrée. Il est borné à 131 072 nœuds, profondeur 256 et 8 192 enfants par nœud. Un dépassement garde **tout le module** dans sa représentation objet : abandonner seulement un worker risquerait de mélanger objets vides et `null` dans le même graphe privé. Les déclarations des classes privées sont conservées, même celle du constructeur vide désormais inutilisée sur le chemin optimisé.

Les fonctions, classes, tags, contrôles de champs et conventions d'application publiques restent identiques. Les workers privés n'ajoutent aucune entrée `$GLOBALS`. Le fichier RBTree passe de **126 203 à 123 361 octets**.

## Vérifications

- **Sept suites AST** : branches, fonctions unaires, boucles compactes, inlining terminal, régions enum, fusion des thunks et constructeurs nullable.
- La nouvelle suite couvre les régions sans enum, ordre inversé des constructeurs, noms renommés/échappés, types ouverts/inconnus, sorties publiques/FFI/callbacks, domaines ADT incompatibles, imbrication de deux types nullable, abaissement dans une closure et abandon atomique sur dépassement de budget.
- **Quinze fixtures PHP et JavaScript** passent avec sorties identiques, dont `NullableRegion`, `InternalEnumRegion`, les contrats FFI/Foreign, racines et callbacks conservés, ainsi que R8/R9/R11 et les régressions de branches/TCO. Voir [fixtures.json](fixtures.json). Le runner ciblé utilise les paquets frères installés ; la suite globale dépend encore de `phpurs-math`, absent de ce checkout.
- La FFI de la nouvelle fixture accepte le contrat `callable` et l'arité saturée du backend. Son premier brouillon imposait une `Closure` à une fonction nommée ; le même échec a été reproduit avec la transformation désactivée avant correction de la fixture. Les arguments ADT conservent leurs contrôles de classes publiques.
- **1 285 insertions** comparées après chaque opération : ordres croissant, décroissant, permutation déterministe de valeurs négatives/positives, doublons et arbre vide. Structures canoniques, ordre et nombre des clés, racine noire, hauteurs noires, absence de rouges adjacents et profondeur calculée sont vérifiés.
- **1 290 paires d'anciennes racines** conservées puis revérifiées, avec sérialisations publiques identiques entre variantes, application partielle publique et `TypeError` sur une mauvaise clé publique. Voir [validation.json](validation.json).
- Rebuild complet : zéro erreur et zéro avertissement du programme benchmark. Le backend rappelle 23 avertissements existants de Main/CodeGen ; aucun nouveau dans NullableConstructors.

Les compteurs séparés, sans JIT, donnent :

| Par action RBTree | Témoin | Intégration |
| --- | ---: | ---: |
| Objets privés `E` | 200 001 | **0** |
| Nœuds privés `T` | 2 583 932 | 2 583 932 |
| Appels privés à `ins` | 2 283 976 | 2 283 976 |
| Appels privés à `balance` après R9 | 0 | 0 |

Voir [counts.json](counts.json). L'algorithme persistant, les 100 000 insertions, les rotations et le parcours final restent inchangés.

## Reproduction

Après `bin/php/run -c` dans `altbak.pub-phpurs`, depuis le checkout PHPurs :

```sh
node tests/codegen/nullable-constructors.mjs
python3 audit/2026-09-09/r10-null-integrated/run-fixtures.py
node audit/2026-09-09/r10-null-integrated/prepare-comparison.mjs
python3 audit/2026-09-09/r10-null-integrated/validate.py
python3 audit/2026-09-09/r10-null-integrated/count.py
python3 audit/2026-09-09/r10-null-integrated/check-state.py
python3 audit/2026-09-09/r10-null-integrated/measure.py full
```

Les sources des benchmarks, PBO et les paquets partagés ne sont pas modifiés. Aucun commit ou merge. La prochaine étape de performance est de reprofiler les coûts restants de RBTree, qui représente encore environ 88 % du total, avant de choisir une nouvelle transformation.
