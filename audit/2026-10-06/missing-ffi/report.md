# M1 — Erreurs explicites pour les FFI absentes

## Résultat

Le générateur remplace l'objet invocable factice des FFI absentes par une
`RuntimeException` indiquant le module PureScript et l'export :

```text
Missing PHP FFI export: Data.Number.floor
```

Ce diagnostic est vérifié sur `Test.WorkloadArrayNumber`, qui échouait auparavant
avec `Return value must be of type Closure|float, class@anonymous returned`.
L'absence de l'implémentation reste une erreur ; ses sondes de somme et de filtre
renvoient chacune `10`. Les workloads ArrayInt et Map renvoient respectivement
`35406173408` et `400040000` avec les deux compilateurs.

## Contrat livré

- Un fichier PHP absent, une source vide et une clé absente de `$exports`
  produisent le même diagnostic au moment de la demande.
- Les fonctions de type `Func` gardent leur wrapper natif. Une référence à la
  fonction ou une application partielle peut être conservée ; un appel saturé
  signale l'absence. Les contrôles PHP d'arguments ont lieu à l'entrée du wrapper,
  avant son corps ; les signatures de résultat restent en place.
- Une valeur étrangère est vérifiée à sa première lecture générée. Cela inclut
  les fonctions brutes `FnN`, les actions `Effect` et les valeurs sans annotation.
  Les valeurs présentes occupent leur slot `$GLOBALS` avec leur représentation
  PHP d'origine. Les valeurs absentes n'ont pas de slot.
- La lecture générée utilise `($GLOBALS[key] ?? getter())`. Le getter distingue
  une véritable valeur `null` d'une clé absente avec `array_key_exists`. Il ne
  force pas les actions et n'ajoute pas de contrôle de type scalaire au résultat.
- Le marqueur nommé `foreignValueArity = -1` distingue ces lectures des globals
  PureScript d'arité zéro. Il est publié et restauré avec les arités du module,
  puis utilisé aussi pour la lecture de `main` dans les deux entrypoints.
- Les noms d'exports et les messages utilisent l'échappement des chaînes du
  printer ; les identifiants contenant une prime sont couverts.

La compilation et le chargement des seules déclarations tolèrent les exports
absents. Une branche non prise ne les demande pas. Un initialiseur qui les lit
ou les appelle constitue en revanche une demande effective. Les entrypoints
modulaires chargent les modules atteignables ; les bundles contiennent tous les
modules chargés et exécutent leurs initialiseurs. Ce cas est testé explicitement.
Le PHP manuscrit qui lit directement `$GLOBALS` se situe hors du contrôle des
lectures générées.

## Régressions

`tests/codegen/missing-ffi.mjs` ajoute neuf vérifications exécutables :

- absence de fichier, PHP vide, clés manquantes et export conditionnel runtime ;
- fonctions natives saturées, applications partielles conservées et répétées,
  signatures polymorphes, valeurs scalaires/inconnues, actions et `Fn2` bruts ;
- valeurs `null`, `false`, `0`, chaîne/tableau vide, identité des objets, closures
  et chaînes invocables, initialisation FFI unique, effets répétés, exceptions
  d'origine et contrôles `Int` d'argument/résultat, y compris l'overflow PHP ;
- lectures locales et qualifiées, appels AST ordinaires/directs, lecture stricte
  avant un consommateur qui ignore son argument et branche non prise ;
- builds froids, chauds, mixtes et sans cache, sources manquantes/vides/rétablies,
  avec comparaison du PHP complet et exécution modulaire/bundle/bundle-only ;
- `main` étranger absent et initialiseur exigeant une valeur dans un module qui
  n'est chargé que par le bundle.

La suite complète `npm run test:codegen` passe : **95 vérifications, zéro échec**.
Le build du compilateur passe sans avertissement. Le test existant des clés de
cache fournit désormais la table d'arités requise par `EntryPointOptions`.

Les cinq fixtures `NativeArrayCallbacks`, `FunctionFFIBoundary`, `PartialBindings`,
`RecursiveDictionaryInitialization` et `EffFn` affichent `Done` en PHP et en
JavaScript. Le bundle de `NativeArrayCallbacks` passe avec la FFI décrite ci-dessous.
`bin/php/run -c` réussit avec les **14 valeurs attendues** et un PHP actif identique
aux **307 fichiers** du compilateur figé après intégration. Le CLI empaqueté est
ensuite restauré avec exactement l'empreinte de l'exécutable validé.

### FFI Lazy du runner

La première exécution du bundle de `NativeArrayCallbacks` a révélé une autre
absence réelle : `Data.Lazy.defer`, demandé par l'initialisation de
`Data.List.Lazy.Types.nil`. Le runner utilise ici le paquet `lazy` du registre,
sans son implémentation PHP, et le bundle inclut cette dépendance des listes.
Son ancien objet factice permettait à cet initialiseur de continuer.

La validation du bundle fournit donc explicitement l'implémentation existante
`../phpurs-lazy/src/Data/Lazy.php` avec `--ffi ../phpurs-lazy` (chemin absolu dans
le script, exécuté depuis `tests/runner`). Ce bundle affiche `Done`. Le journal
de l'échec initial est conservé dans `audit/integration-initial/` des artefacts.

## Comparaison du corpus et mesures

`compare.py` utilise deux exécutables figés et le même corpus de **306 CoreFn**,
les mêmes FFI et les mêmes dépendances Composer. Le témoin est l'exécutable
intégré de l'audit Array précédent ; ses **307 fichiers PHP** sont identiques
octet pour octet à ceux de cet audit. L'intégration conserve ce jeu de fichiers,
en modifie **129**, et retire tous les objets factices de FFI.

| Mesure | Avant | Après |
| --- | ---: | ---: |
| Taille des 307 fichiers PHP | 7 380 790 octets | 7 423 151 octets |
| Somme des temps de la suite, passage 1 | 67,112 ms | 66,971 ms |
| Somme des temps de la suite, passage 2 | 66,742 ms | 67,417 ms |
| Valeurs attendues vérifiées à chaque passage | 14/14 | 14/14 |

Ordre : avant, après, après, avant. Les temps sont ceux du protocole existant,
avec JIT `1255`, OPcache CLI activé, cache de fichiers OPcache vide et Xdebug
désactivé. Ces quatre passages sont un contrôle de fonctionnement et d'ordre de
grandeur, pas une estimation précise de l'effet sur les performances. Le PHP
supplémentaire représente **42 361 octets**, environ **0,574 %**.

Les **590 empreintes d'entrées/sources**, les exécutables figés, les manifests
PHP et les dépendances vendor sont contrôlés par le script. Les sorties détaillées,
les valeurs des 14 tests et les empreintes sont dans les artefacts référencés par
[`results.json`](results.json).

## Reproduction

Depuis la racine PHPurs, avec le fork TAST sur le PATH pour les fixtures :

```bash
npm run build
npm run test:codegen > /chemin/validation-codegen.log 2>&1

python3 audit/2026-10-06/missing-ffi/compare.py \
  --artifacts /chemin/nouveau-dossier-audit \
  --before /chemin/audit-array/integrated.cjs \
  --control /chemin/audit-array/integrated/output

python3 audit/2026-10-06/missing-ffi/integration.py \
  --artifacts /chemin/nouveau-dossier-audit \
  --codegen-log /chemin/validation-codegen.log
```

Le premier script fige le compilateur courant, régénère les deux corpus,
vérifie leur intégrité, lint les fichiers modifiés, exécute les workloads et les
quatre passages de la suite. Le second valide les fixtures PHP/JavaScript,
le bundle avec sa FFI Lazy, puis `bin/php/run -c`, compare le PHP actif au corpus
figé et restaure le CLI empaqueté avec son identité de cache vérifiée.

La collecte finale a utilisé `integration.py --completed-logs` après correction
d'une collision de noms Python dans le collecteur : les journaux des commandes
déjà réussies ont été revérifiés, puis les entrées, le PHP actif, vendor et le CLI
restauré ont subi leurs contrôles d'intégrité. Les exécutions mesurées ne sont pas
rejouées par cette option.
