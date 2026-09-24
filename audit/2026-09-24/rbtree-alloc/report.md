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
