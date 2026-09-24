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
