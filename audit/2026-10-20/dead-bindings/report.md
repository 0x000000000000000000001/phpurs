# R7 — DCE des bindings annulée

Le 20 octobre 2026. Sur les 108 modules retenus, **574 fonctions globales** ne
sont référencées ni par `$GLOBALS`, ni par un appel direct dans le PHP généré ;
**568 sont exportées**. Leur suppression sur une copie fait passer le PHP de
**1 766 903 à 1 271 079 octets** (−28,06 %) et conserve les 14 valeurs.

## Prototype

La copie a été obtenue à partir de la sortie filtrée de `App`. Pour chaque
module, le prototype retire la déclaration `function ...` et l'affectation
`$GLOBALS['Key'] = __NAMESPACE__ . '\\...'` lorsque ni la clé ni le nom de
fonction n'apparaît ailleurs dans les 108 fichiers. Les constructeurs, les
valeurs, les initialisations et les wrappers FFI ne sont pas visés par cette
reconnaissance.

Résultat : 307 → 109 fichiers (déjà filtrés), **1 271 079 octets**, 14 valeurs
validées, total observé **64,91 ms** (non attribué causalement).

## Pourquoi la passe est annulée

Le prototype supprime des fonctions **exportées**. Le test `rewrite-limit` a
immédiatement révélé le contre-exemple : `Limit.run`, exporté mais non référencé
dans le graphe `main`, est appelé directement par le harness et disparaît. Une
DCE par défaut casserait donc les exports publics. Une DCE de production doit :

- soit rester opt-in pour un build « application » dont tous les appels sont
  internes, soit préserver les exports publics ;
- construire un graphe inter-modules des `Var` qualifiées et des références
  directes, avec les mains, constructeurs, FFI et initialisations comme racines ;
- conserver les fonctions qu'une FFI peut appeler par nom ou par chaîne
  construite dynamiquement, ou documenter cette limite ;
- préserver les initialisations top-level et les effets, même sans référence ;
- définir l'identité de cache et de manifeste quand un build plus étroit ou plus
  large produit des ensembles de bindings différents ;
- tester un ancien export, une FFI statique/dynamique, des SCC récursives, des
  constructeurs et une initialisation observable.

Le gain mesuré justifie l’activation par défaut pour un build « application ».
`--no-dce-exports` conserve toute la surface publique pour les usages
bibliothèque ou les appels PHP externes.

## Décision

La DCE n’est **pas intégrée**, ni par défaut ni derrière un flag. Le prototype a
supprimé `Limit.run`, exporté mais appelé directement par un harness PHP ; ce
n’est pas un détail de test, c’est une modification de la surface publique des
modules. Une passe correcte devrait soit préserver les exports (et ne retirer
que quelques fonctions non exportées), soit introduire un contrat de build
« application » explicite, avec cache et manifeste dédiés. Ce n’est pas
suffisamment stabilisé pour être livré.

## Validation courante

Le filtrage des modules et le découplage Composer sont validés par **100/100**
contrôles codegen et par `runp` (14 benchmarks, lock inchangé). La DCE est
abandonnée ; le runtime partagé reste la dernière étape R7.

Artefacts :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-r7-dce-prototype-20261020`.
