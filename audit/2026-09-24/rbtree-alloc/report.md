# RBTree : comptage des allocations et borne supérieure de la réutilisation en place

Reconnaissance avant transformation : les comptages historiques sont
reconfirmés sur le build courant (`phpurs` `9f280ea8`, build compilé frais,
TAST identique), puis une variante expérimentale non sûre chiffre le gain
atteignable en réutilisant le nœud visité au lieu de le reconstruire.

## Comptages (build courant, bundle 9f280ea8)

Instrumentation des constructeurs et des fonctions dans une copie jetable,
exécution de `Test_RBTree_act` (résultat 22), sans JIT :

    Test_RBTree___phpurs_enum_0_T : 2 583 932
    ins (privé)                   : 2 283 976 appels
    buildTree (privé)             : 1 appel
    depth (privé)                 : 200 001 appels
    balance, makeBlack            : 0 (inlinés dans ins)
    classes publiques R/B/E/T     : 0 allocation

Identiques à l'audit copy-cleanup du 9 septembre : la base n'a pas bougé.

## Attribution des allocations T (par site de construction)

| Site | Allocations | Part |
| --- | ---: | ---: |
| Reconstruction du nœud visité `new T(v_0, v1_1, v2_2, v3_3)` (plusieurs branches) | 2 083 998 | 80,7 % |
| Rotation (3 nœuds par branche, 1 site) | 299 934 | 11,6 % |
| Feuille initiale `new T(0, null, x, null)` | 100 000 | 3,9 % |
| Re-noircissement de la racine (`insert`) | 100 000 | 3,9 % |

Par fonction : `ins` 2 483 932 (96,1 %), `insert` 100 000 (3,9 %).

## Borne supérieure (expérience non sûre)

Les sites de reconstruction pure et le re-noircissement de racine sont
remplacés par des **écritures de champs sur le nœud visité** (`inplace2`),
les rotations et la feuille restant allouées :

- résultat de `act` toujours 22 ;
- allocations T restantes : **399 934** (feuille 100 000 + rotations 299 934),
  soit **-84,5 %** ;
- temps ABBA sur machine chargée (2 tours, meilleur sur 3 appels à froid) :
  - original : 236,2 / 234,8 ms
  - mutation : 161,5 / 163,2 ms

Soit environ **-31 %** sur RBTree. La variante avec tableau temporaire
(`inplace`, un `$__args = [...]` par nœud) n'apportait rien : le coût du
tableau remplaçait l'allocation supprimée. L'écriture directe est le bon
schéma.

## Portée et limites

La mutation n'est pas sûre en général : elle corrompt toute copie de l'arbre
conservée par l'appelant (partage structurel). La fixture CopyCleanup qui
échange deux arbres sur 20 000 itérations serait cassée. Il faut donc une
preuve d'unicité/dernière utilisation avant de générer ces écritures.

Pistes bornées :

1. **ADT privés en tableaux PHP** : le copy-on-write des tableaux fournit
   exactement « écrire si unique, copier si partagé ». Sûr et général, mais
   refonte de CodeGen (construction, accès, discrimination de constructeurs,
   motif) pour les constructeurs privés.
2. **Analyse de consommation sur les régions fermées** (`EnumRegions`) :
   autoriser la mutation d'un worker privé quand tous ses appels reçoivent
   une valeur en dernière utilisation et qu'aucune référence ne s'échappe.
   Ciblé, mais demande une analyse interprocédurale.
3. **Élimination des reconstructions identiques** (renvoyer le nœud source
   quand aucun champ ne change) : petite, sûre, limite le gain au
   re-noircissement (~3,9 %).

## Fichiers

Les scripts de comptage/mesure et les résultats JSON sont dans le répertoire
de tâche utilisé pour cet audit ; les copies de `Test.RBTree/index.php`
instrumées n'y sont pas conservées (régénérables par les scripts).

## Borne sous les conditions réelles (JIT 1255)

Le protocole du banc active OPcache CLI, JIT 1255 et un buffer de 128 MiB
(`altbak.pub-phpurs/bin/php/driver.py`). Re-mesure ABBA (8 processus, meilleur
et médiane sur 10 appels chauds) :

| Variante | min (ms) | médianes des runs |
| --- | ---: | --- |
| original | 106,1 | 112,3 / 112,2 / 110,2 / 112,0 |
| mutation en place | 54,6 | 57,3 / 59,5 / 55,9 / 55,7 |

Soit **≈ ×2** : la borne est bien plus élevée sous JIT que sans (≈ -31 %).
La suppression des allocations interagit fortement avec le JIT.

## Micro-banc des représentations (n = 100 000, JIT 1255)

`micro-4-representations.php` réimplémente l'algorithme du banc avec quatre
représentations ; comptage des constructions explicites :

| Variante | ms (meilleur) | constructions | mémoire |
| --- | ---: | ---: | ---: |
| A — objets, fonctionnel (actuel) | 148,0 | 2 483 948 | 14 MiB |
| B — objets, mutation du nœud visité | 53,2 | 399 934 | 14 MiB |
| C — tableaux, paramètres par référence | 62,7 | 399 934 | 22 MiB |
| D — tableaux, passage par valeur | 120,4 | 2 483 948 | 22 MiB |

C est le point clé : le copy-on-write des tableaux combiné à des paramètres
par référence donne des mises à jour en place (même nombre de constructions
que B, soit -84 %) pour **2,36× le débit de A**, tout en séparant les valeurs
partagées au lieu de les corrompre. Le passage par valeur (D) copie à chaque
niveau et est plus lent que A.

## Design de la transformation sûre

Représentation privée : les constructeurs de région deviennent des tableaux
packed (`[champ0, champ1, …]`, `null` pour le constructeur vide nulifiable),
les travailleurs privés prennent leurs paramètres ADT **par référence**.

Sûreté (sans analyse d'alias, par le COW) :

1. un argument passé par référence dont le liage est vivant est d'abord copié
   dans un temporaire (`$tmp = $arg ; worker(…, $tmp)`) : la séparation PHP
   protège la valeur partagée ;
2. un liage en dernière utilisation est passé directement : refcount 1, donc
   écriture en place ;
3. un accès de champ d'une valeur elle-même possédée (paramètre by-ref) est
   passé par référence ; un accès à une valeur partagée est copié ;
4. le partage interne (DAG) reste correct : la première écriture sépare.

Points d'insertion relevés dans `phpurs/src/Phpurs/CodeGen.purs` :

- `rawDecls` (~ l.760) : ne plus émettre de classe pour ces constructeurs ;
- `CtorSaturated` (~ l.614) et `CtorDef` (~ l.633) : littéral de tableau ;
- `Accessor (GetCtorField …)` (~ l.399) : index de tableau ;
- `OpIsTag` (~ l.82) : comparaison à `null` (layouts nulifiables) ou à l'étiquette ;
- signatures des travailleurs privés (`internalSignatures`/`hideWorker`, ~ l.923) :
  `&` sur les paramètres ADT ;
- sites d'appel (`App`/`UncurriedApp`, ~ l.250/301) : règle 1/2/3 ci-dessus,
  le contexte d'usage étant déjà disponible (`totalUsagesOf`, `tcoAnalysisOf`) ;
- `EnumRegions` expose déjà `privateNames`, `privateConstructors`,
  `nullableConstructors` ; il faudra y ajouter la liste des constructeurs
  représentés en tableaux.

Périmètre initial recommandé : layouts privés « produit nulifiable » (un
constructeur à champs, éventuellement un constructeur vide) — c'est le cas de
`Tree` (E nulifié + T) et de l'essentiel des ADT scalaires des bancs ; les
sommes privées non nulifiables restent en classes dans un premier temps.
Banc de régression : RBTree (résultat 22), fixture CopyCleanup (racines
conservées), tests codegen complets.

## Étape 1 (représentation en tableaux) : implémentée, validée, puis retirée

Implémentation dans `EnumRegions.rewrite` : le constructeur boîte d'un layout
privé nulifiable devient `Lit (LitArray …)`, ses accès deviennent
`Op2 OpArrayIndex`, les tests de motif restent `OpIsTag` (le passe
`Nullable.lower` les transforme en `=== null` / `!== null`, ce qui reste
correct pour un tableau non nul).

Validation :

- suite codegen complète : **10/10** (l'assertion de représentation du test
  `nullable-constructors.mjs` mise à jour vers `[…]`) ;
- build réel du banc : `act = 22` sans JIT, aucun `new …___phpurs_enum_0_T(`
  restant, accès `($x)[i]` ;
- sous le protocole réel (JIT 1255) : `act = 22` sur 20 essais et un stress de
  100 appels, mais **deux `zend_mm_heap corrupted` intermittents** (~2
  processus sur ~20). Le micro-banc C écrit à la main (tableaux, paramètres
  par référence) n'a jamais planté (0/9), donc la corruption vient d'une forme
  de code générée et non des tableaux en soi.

Décision : retirer l'étape 1 tant que le déclencheur JIT n'est pas réduit ;
la build est revenue à la représentation objets (suite codegen 10/10, stress
objets stable). Le diff est conservé dans `stage1-arrays.patch`.

Piste : la variante JIT-stable du micro-banc passe ses valeurs **par
référence** ; la corruption n'apparaît que dans la variante générée
by-value. L'étape 2 (paramètres by-ref) pourrait être nécessaire à la fois
pour la performance et pour la stabilité JIT — à vérifier par un reproducer
minimal avant toute republication.

## Reproducer JIT : conclusion (faux positif)

L'investigation du `zend_mm_heap corrupted` a été reprise : le déclencheur
n'est **pas** la représentation en tableaux.

- mêmes commandes, fenêtres différentes : 12/12 échecs puis 0/10, 4/4 puis
  4/4 succès, y compris à `memory_limit=128M` (défaut) sur la build
  tableaux ;
- la build **objets** passe dans ces mêmes fenêtres, mais rien ne prouve
  qu'elle soit épargnée en général ;
- machine partagée (load 10–20, mémoire libre abondante), aucun déclencheur
  unique identifié ; le motif est temporel (fenêtres d'échecs), pas structurel.

Conséquence : ces corruptions sont un aléa d'**environnement** à surveiller
pendant les campagnes (processus perdus possibles) ; ce n'est pas un argument
contre la représentation en tableaux. En revanche, la mesure sous protocole
reste non concluante tant que la machine n'est pas stable : la campagne finale
devra être planifiée dans une fenêtre calme.

État : étape 1 (tableaux) dans `stage1-arrays.patch`, non appliquée ;
l'étape 2 (paramètres by-ref) reste à écrire et est nécessaire à la fois pour
le gain (×2 attendu) et pour diviser l'empreinte mémoire transitoire.

## Étape 2 (by-ref) : règle de possession et obstacle TCO

Sondage du code généré de `buildmajTree` (avant optimisation) :

    tco_loop:  $v_0  = $__tco_var_…_v_0;   // registres de boucle
               $v1_1 = $__tco_var_…_v1_1;
               switch($v_0) { case 0: $__res = $v1_1; goto __end; … }
               $__tco_0 = ($v_0 - 1);
               $__tco_1 = insert($v_0, $v1_1);        // ← la valeur est ici
               $__tco_var_…_v1_1 = $__tco_1;         // consommée
               goto tco_loop;

`$v1_1` a **deux lectures textuelles** (la sortie `case 0` et l'appel), donc
une règle « lecture unique » refuserait le passage direct et toute la chaîne
recopierait (pas de gain). Une lecture est pourtant morte sur le chemin de
l'appel : il faut soit une **liveness par CFG** (les `goto`/`switch` TCO en
font partie), soit des **motifs de déplacement** reconnus :

1. paramètre by-ref de la fonction courante → possédé ;
2. `index` d'un paramètre possédé → possédé ;
3. registre de boucle TCO : assigné depuis un porteur en tête de boucle,
   aucune lecture après l'appel, porteur réécrit avec le résultat — la valeur
   est consommée ; on peut passer le porteur et libérer le registre
   (`unset`) avant l'appel pour retomber à refcount 1.

C'est le préalable à implémenter avant toute republication. La build est
laissée à la représentation objets (stable, tests verts) ; l'étape 1 reste
dans `stage1-arrays.patch`.

## Mise au point (tentative B) : le déclencheur est le harnais, pas la représentation

- build **objets** (publiée) + `json_encode` après la charge : **3/3
  `zend_mm_heap corrupted`** sous JIT 1255 ; la même build sans
  `json_encode` passe 4/4 ;
- build **tableaux** : crashs 4/4 avec un driver maison (stdin, 8 actes,
  hrtime + sprintf + memory_get_peak_usage), puis 3/3 succès avec des
  commandes `-r` équivalentes (4, 6, 8, 10 actes) ;
- conclusion : bug **JIT de PHP** déclenché par le code d'instrumentation
  exécuté dans le même processus (json_encode, driver custom), **les deux
  représentations** sont concernées, la flakiness ne permet pas d'incriminer
  les tableaux.

Conséquence méthodologique : les mesures finales doivent se faire avec le
**harnais natif du banc** (`runp`/`Bench.purs`), pas avec des drivers
instrumentés ; sinon le processus peut mourir pour une raison étrangère au
code mesuré. L'étape 1 (tableaux) reste dans le patch ; l'étape 2 (by-ref) et
la campagne native restent le prochain jalon.

## Verdict natif (harnais du banc, protocole réel)

Build tableaux (Stage 1, by-value) avec le bundle `89be409d`, exécutée par
`bin/php/run --run-only` (harnais `Bench.purs`, aucun driver externe) :

    Global warm-up in progress...
    zend_mm_heap corrupted      (PHP exit -6)

La campagne native ne peut donc pas tourner avec la représentation en
tableaux **telle quelle** : le bug JIT de PHP frappe le warm-up global. La
build objets, elle, passe le même harnais (campagnes publiées).

Lecture : la variante tableaux+by-ref du micro-banc (C) est stable sous JIT,
donc l'étape 2 pourrait à la fois apporter le ×2 et changer la forme de code
qui déclenche le bug — mais ce n'est pas acquis. Prérequis avant toute
republication : un build tableaux+by-ref qui passe le warm-up natif.

État : compilateur restauré (représentation objets), tests codegen verts,
patch dans `stage1-arrays.patch`.

Piste d'isolation pour la prochaine session : `bin/php/run` accepte
`--test <nom>` (voir `driver.py`, modes `--ffi/--fficc/--test`) ; lancer le
harnais natif test par test permet de savoir si RBTree lui-même déclenche le
`zend_mm_heap corrupted` ou si c'est un autre banc passé en tableaux. À faire
avant d'accuser l'étape 2.

## Isolation : RBTree seul crashe nativement

`bin/php/run --test RBTree` avec le compilateur Stage 1 (bundle `89be409d`) :
la phase « Output & Warm-up » du **seul** test RBTree meurt en
`zend_mm_heap corrupted` (exit -6). Le déclencheur est donc le code tableaux
de RBTree lui-même, pas un autre banc de la suite.

Conséquence : la représentation by-value n'est pas publiable pour RBTree.
L'étape 2 (by-ref) est le test décisif : le micro-banc C correspondant est
stable sous JIT 1255, mais cela reste à confirmer sous harnais natif
(`--test RBTree`).

État : compilateur restauré (objets), tests codegen verts ; patch et audit à
jour.

## Nature du crash : bug de JIT (pas notre code)

Sur le build RBTree isolé (AppX, tableaux) :

- `Test_RBTree_act()` appelé directement : `act=22`, 3/3 succès ;
- AppX complète (harnais Bench) : `zend_mm_heap corrupted` sous
  `opcache.jit=1255` ;
- **AppX complète sans JIT (`opcache.enable_cli=0`) : passe** (« Batch
  iterations: 1 ») ;
- en JIT fonction `1205` : erreur avec trace (à qualifier), mais pas la même
  corruption.

Donc le code tableaux est correct sans JIT ; c'est l'exécution sous **tracing
JIT** dans le contexte du harnais qui déclenche le bug PHP. Piste de
mitigation/validation : la variante by-ref (étape 2) change la forme des
traces ; à rejouer sous `--test RBTree`.

## Expérience : TCO neutralisé — le crash persiste

Stage 1 + `TailInline.optimize` court-circuité (bundle `3fc332ef`), test
RBTree isolé : **toujours `zend_mm_heap corrupted`** au warm-up sous JIT
1255. La boucle TCO n'est donc pas la forme fautive.

Bilan des hypothèses testées :

| Facteur | Verdict |
| --- | --- |
| `json_encode` / instrumentation externe | non — le crash est natif |
| mémoire (`memory_limit`) | non — floconneux, pas corrélé |
| boucle TCO (`goto`) | non — persiste sans TCO |
| JIT tracing 1255 | **oui** — sans JIT et en 1205, le même code passe |
| représentation tableaux | **impliquée** (objets passent en 1255, tableaux non) |

Le déclencheur est donc l'interaction **représentation en tableaux × tracing
JIT 1255** dans le contexte du harnais, indépendamment de la forme de boucle.
Candidats restants : passer à la variante by-ref (micro-banc C stable en
1255), réduire la forme (par ex. éviter `null` dans les tableaux), ou
documenter le bug PHP avec le build isolé comme reproducer.

## Expérience : sentinelle `[]` (sans `null`) — le crash persiste

Stage 1 + `NullableConstructors` avec tableau vide `[]` au lieu de `null`
(bundle `178e3239`) : RBTree isolé crashe **encore** au warm-up en 1255.
Le `null` dans les tableaux n'est donc pas le déclencheur.

Table finale des hypothèses :

| Facteur | Verdict |
| --- | --- |
| instrumentation / `json_encode` | non (crash natif sans driver) |
| mémoire | non (floconneux, non corrélé) |
| boucle TCO | non (persiste sans TCO) |
| `null` dans les tableaux | non (persiste avec sentinelle `[]`) |
| tracing JIT 1255 | **oui** (sans JIT et en 1205 : passe) |
| représentation en tableaux | **condition nécessaire** (objets OK en 1255) |

Il reste deux voies : (1) la variante **by-ref** (micro-banc C stable en
1255, non testée sous harnais natif) ; (2) documenter le bug PHP avec le
build isolé comme reproducer et repartir sur la voie objets + preuve
d'unicité (borne ×2 mesurée, JIT-stable).

## Étape 2 réussie : objets + mutation en place → ×2, natif validé

`Phpurs.ArrayRefs` est implémenté et branché (après `CopyCleanup`) :

- possession : paramètre promu, champ d'un paramètre promu, ou registre de
  boucle TCO consommé (porteur libéré) ; un argument non prouvé laisse le
  paramètre by-value (fixpoint par élimination des violations) ;
- reconstruction : `new T(champs du nœud)` où un champ est remplacé par le
  résultat récursif devient une **écriture de champ** puis renvoie le nœud ;
  une reconstruction aux champs inchangés renvoie simplement le nœud ;
- `Printer` : `&` imprimé au-delà du premier argument ;
- `functionParts` couvre les IIFE (`PhpGlobalAssign x (PhpCall (PhpFunction
  …) [])`) : sans cela les appels dans `act` échappaient au fixpoint et PHP
  émettait « Only variables should be passed by reference ».

Résultats :

| Mesure | Valeur |
| --- | ---: |
| RBTree isolé natif (JIT 1255, best of 10) | **54,3 ms** |
| publié (build objets, mêmes flags) | ~104 ms |
| tests codegen | **10/10** |
| corruption JIT | aucune |

Les reconstructions restantes sont les rotations (64 `new`) ; le résultat 22
est validé par le harnais natif.

## Résultat final (25 septembre) : suite native complète validée

`bin/php/run` (harnais natif, JIT 1255) : **exit 0**, `values_validated=true`,
**total 72,94 ms** (vs 129,49 ms avec le compilateur sans l'optimisation ;
116,59 ms publié).

| Test | Temps (ms) |
| --- | ---: |
| Red-Black Tree (100k Worst-Case Insertions) | **59,79** |
| Polymorphism (10M Type Class Dict Lookups) | 6,83 |
| Church Numerals (100k Closure Applications) | 2,21 |
| Deep Record Updates (10k iterations) | 1,53 |
| Lazy Evaluation | 0,85 |
| Prime Sieve | 0,81 |
| State Monad | 0,52 |
| List Processing | 0,16 |
| Array Processing | 0,10 |
| Tail Call Optimization | 0,08 |
| Ackermann | 0,05 |
| AST Evaluation / Fibonacci / RowToList | ~0,002 |

Robuste : RBTree 59,8 ms contre ~104 ms publié, `ins`/`insert` promus,
reconstructions en place (les rotations restent allouées), `buildTree`/`depth`
laissés by-value (appelés avec `null` / résultat d'appel).

Bugs corrigés en chemin, tous documentés ci-dessus : applications curryfiées
(flattening), queues de branche conscientes du flux (terminateurs `return`/
`throw`, `goto`), porteur TCO assigné à `k+2`, IIFE non vues par le fixpoint,
`&` imprimé au-delà du premier argument.

Prérequis de republication : campagne multi-processus en fenêtre calme (la
machine reste partagée).
