# R5 — Potentiel de fusion des mises à jour de records

Le 19 octobre 2026. **Mesure sur copie, pas encore intégrée au générateur.**
Remplacer les trois `clone` + affectations de `Test.Records` par une construction
imbriquée fait passer l'action de **1 579 à 1 112 µs** en médiane ABBA, soit
**−29,6 %** et environ **0,467 ms** de gain direct dans la suite.

## Prototype

[`prepare.py`](prepare.py) copie la sortie benchmark et transforme uniquement le
corps de `updateRec`. La variante fusionnée remplace :

```php
$__obj0 = clone $v1_1;
($__obj0)->{'a'} = ...;
$__obj1 = clone ($v1_1)->{'b'};
...
($__obj0)->{'b'} = $__obj1;
```

par une construction en une passe :

```php
$__obj0 = (object)['a' => ..., 'b' => (object)['c' => ...,
  'd' => (object)['e' => ..., 'f' => ...]]];
```

Les lectures et l'ordre d'évaluation des champs sont conservés. Les anciens
records ne sont pas modifiés : la valeur initiale et les anciennes racines
restent observables.

## Mesure isolée

PHP 8.5.4, JIT 1255, buffer 128 MiB, OPcache CLI actif, cache fichier désactivé.
[`measure-records.php`](measure-records.php) exécute la vraie action
`Test.Records.act` (10 000 itérations), vérifie le résultat **20 000** et calibre
au moins 20 ms par lot. [`measure.py`](measure.py) utilise l'ordre
`baseline, fused, fused, baseline, baseline, fused, fused, baseline`.

| Variante | Médiane des quatre processus |
| --- | ---: |
| Trois clones + mutations | 1 578,733 µs |
| Construction imbriquée | 1 111,641 µs |

Échantillons : baseline 1 562,229 / 1 594,646 / 1 562,820 / 1 615,148 µs ;
fusionné 1 101,367 / 1 100,949 / 1 121,915 / 1 153,081 µs. Les résultats et les
huit lignes complètes sont dans [`results.json`](results.json).

## Contre-exemple de mutation

Une variante qui supprime les clones et mute directement `$v1_1` a été testée
puis écartée : après deux actions, le résultat vaut **40 000** au lieu de
20 000. La mutation change la valeur conservée par l'appelant et l'état initial.
Le clone reste le chemin de repli obligatoire tant qu'une preuve d'absence
d'alias et d'échappement n'existe pas.

## Conditions d'intégration

La construction fusionnée n'est pas encore une transformation générique. Une
intégration doit :

- prouver la fermeture et l'ordre complet des labels du record dans le TAST ;
- préserver l'ordre d'évaluation des valeurs mises à jour et des autres champs ;
- préserver l'ordre des propriétés observables, y compris pour un record fourni
  par la FFI dont l'ordre PHP peut différer ;
- ne fusionner que des mises à jour imbriquées dont la base et les
  intermédiaires n'ont pas d'autre usage ;
- conserver le clone comme repli et tester un ancien record, un alias de
  sous-record et une closure qui capture l'ancienne valeur.

Le gain mesuré justifie une micro-étape d'intégration bornée. Aucun changement
du générateur n'a été effectué pour cette mesure.

Artefacts complets :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-r5-prototype-20261019`.

## Intégration dans CodeGen

La transformation est implémentée dans `CodeGen.translateTyped` et
`tryFusedUpdate`. Elle s'applique uniquement lorsqu'un `Update` est enveloppé
par un type `Record (Row fields Nothing)` et que :

- les propriétés couvrent exactement tous les labels de la ligne fermée ;
- chaque mise à jour imbriquée cible le champ attendu de la même racine **locale** ;
- chaque niveau imbriqué satisfait la même couverture complète.

Une racine globale (`Var`) garde le clone : sa lecture peut être un getter FFI
demandé à l'exécution, et la sauter changerait le moment d'une erreur. Une ligne
vide ou une mise à jour sans propriété garde aussi le clone.

Les valeurs sont traduites dans l'ordre des propriétés d'origine ; la
construction finale suit l'ordre des labels de la ligne. Les mises à jour
partielles, les lignes ouvertes et les chemins qui ne correspondent pas au champ
attendu gardent le clone existant. Les records mis à jour dans un test réel
gardent leur ancienne valeur, leurs alias et leurs captures.

Après régénération, **seul `Test.Records/index.php` change** parmi les 307
fichiers par rapport au contrôle R1. Le PHP total passe de 7 430 597 à
7 430 414 octets (−183). La suite ABBA donne Records **1 575,9 → 1 103,0 µs**
(−30,0 %) ; le total passe de 70,82 à 70,62 ms, variation non attribuée car
RBTree et Church dominent l'écart. Les 14 valeurs restent identiques.

Les mesures intégrées complètes sont dans
[`integrated-results.json`](integrated-results.json). Le test
[`record-update-fusion.mjs`](../../../tests/codegen/record-update-fusion.mjs)
couvre construction complète, update partiel avec clone, ligne ouverte et racine
globale avec clone, alias, ordre des clés, capture de l'ancien record et
exception avant construction. La fixture
`RecordUpdateFusion.purs` exécute le même contrat via le pipeline réel.

Build sans warning, **97/97** contrôles codegen et cinq fixtures ciblées
(`RecordUpdateFusion`, `ExactArity`, `PartialBindings`, `ImmediateStateFusion`,
`FunctionFFIBoundary`) passent. La sortie benchmark active a été régénérée avec
le nouvel exécutable : `Test.Records` ne contient plus de clone, les 14 valeurs
passent, et la mesure active donne environ **1 040 µs** pour Records.
