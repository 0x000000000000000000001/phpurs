# Plan de cleanup — maintenabilité

Objectif : rendre le code compréhensible et modifiable par des humains
(bibliothèque, CLI, frontières FFI et tests). La performance n’est plus un
objectif actif ; les bilans historiques restent dans
[`audit/2026-10-20/todo-archive/performance-backlog-2026-10-20.md`](audit/2026-10-20/todo-archive/performance-backlog-2026-10-20.md).

## Non-objectifs

- Pas de nouvel benchmark de performance.
- Pas d’optimisation runtime ou de taille de PHP dans ce plan.
- Pas de comparaison de temps dans les critères d’acceptation.
- Pas de suppression d’audit historique.

## Règles

- Un changement révisable à la fois.
- Préserver le comportement observable, les contrats FFI et les erreurs.
- Commencer par cartographier, puis extraire ; ne pas mélanger déplacement et
  changement d’algorithme.
- Chaque phase laisse le build et les tests verts.
- Une extraction est terminée quand ses tests ciblés passent et que la
  documentation de l’architecture pointe vers le nouveau module.

## État de référence au 20 octobre 2026

- `npm run build` passe.
- `npm run test:codegen` : **101/101**.
- Les gros modules sont `CodeGen.purs` (946 lignes), `ArrayRefs.purs` (733),
  `Printer.purs` (494), `EnumRegions.purs` (402) et `Main.purs` (242).
- Le code mélange encore orchestration, passes AST, noms PHP bruts et
  publication de sorties dans quelques fichiers centraux.
- Les audits et l’index sont disponibles dans `audit/` et `audit/README.md`.

## Phase 1 — Hygiène (terminée)

- [x] Index d’audit et statuts indicatifs : [`audit/README.md`](audit/README.md) (64 rapports).
- [x] Vérification `.gitignore` / `.npmignore` et des artefacts locaux ; paquet
  npm réduit aux sources, docs, assets et CLI. Voir [repo-hygiene](audit/2026-10-20/repo-hygiene/report.md).
- [x] Inventaire des imports/exports et frontières FFI ; aucun import explicite
  inutilisé détecté. Voir [import-inventory](audit/2026-10-20/import-inventory/report.md).
- [x] Chemin de build unifié : le runner benchmark utilise `tools/bundle.mjs`
  comme le build npm. Voir [build-path](audit/2026-10-20/build-path/report.md).

## Phase 2 — Découper le générateur

- [x] Cartographier les dépendances internes de `CodeGen.purs` et identifier
  les groupes cohérents avant toute extraction. Groupes types, TCO, Effect,
  records, bindings et orchestration identifiés ; extraction Types puis Tco
  proposée ([bilan](audit/2026-10-20/codegen-map/report.md)).
- [x] Extraire les types et l’état de traduction dans `CodeGen/Context.purs` ;
  `CodeGen` les réexporte et l’API existante reste stable. `CodeGen.purs`
  946 → **902 lignes**, 101/101 tests, 110 fichiers PHP identiques
  ([bilan](audit/2026-10-20/codegen-context/report.md)).
- [x] Extraire les helpers TCO (contexte de boucle, labels, registres,
  temporaires) dans `CodeGen/Tco.purs`, avec une API explicite et sans changer
  l’algorithme. `CodeGen.purs` 902 → **855 lignes**, module TCO de 68 lignes,
  101/101 tests, 110 fichiers PHP identiques
  ([bilan](audit/2026-10-20/codegen-tco/report.md)).
- [x] Évaluer l’extraction des branches/jonctions : non retenue, car elle
  imposerait des callbacks dans le noyau récursif sans réduire la complexité.
  Décision consignée dans [le bilan de clôture](audit/2026-10-20/cleanup-closeout/report.md).
- [x] Extraire la détection/le rendu Effect dans `CodeGen/Effects.purs`.
  `CodeGen.purs` 855 → **834 lignes**, module Effects de 33 lignes,
  101/101 tests, 110 fichiers PHP identiques
  ([bilan](audit/2026-10-20/codegen-effects/report.md)).
- [x] Extraire l’analyse des bindings dans `CodeGen/Bindings.purs`
  (`analyzeBindings`, `isSafeRecursiveInit`, `extractUncurriedAbs`).
  `CodeGen.purs` 834 → **763 lignes**, 101/101 tests, 110 fichiers PHP
  identiques ([bilan](audit/2026-10-20/codegen-bindings/report.md)).
- [x] Extraire la réécriture des reconstructions dans `ArrayRefs/Rebuild.purs`
  et les helpers partagés dans `ArrayRefs/Support.purs`. `ArrayRefs.purs`
  733 → **646 lignes**, 101/101 tests, 110 fichiers PHP identiques
  ([bilan](audit/2026-10-20/arrayrefs-rebuild/report.md)).
- [x] Séparer l’analyse d’ownership restante (`fixpoint`, scans de passabilité)
  dans `ArrayRefs/Analysis.purs`. `ArrayRefs.purs` 646 → **81 lignes**,
  101/101 tests, 110 fichiers PHP identiques
  ([bilan](audit/2026-10-20/arrayrefs-analysis/report.md)).
- [x] Séparer dans `Printer.purs` les expressions, les déclarations et
  l’assemblage d’un module. Façade 28 lignes, `Printer/Expr` 346 lignes,
  `Printer/Decl` 172 lignes ; 101/101 tests, 110 fichiers PHP identiques
  ([bilan](audit/2026-10-20/printer-split/report.md)).
- [x] Extraire la sélection des mains et la fermeture CoreFn dans
  `Main/Selection.purs` ; `Main.purs` 242 → **219 lignes**, 101/101 tests,
  110 fichiers PHP identiques ([bilan](audit/2026-10-20/main-selection/report.md)).
- [x] Extraire la finalisation des sorties dans `Main/Outputs.purs`.
  `Main.purs` 219 → **183 lignes**, 101/101 tests, 110 fichiers PHP identiques
  ([bilan](audit/2026-10-20/main-outputs/report.md)).
- [x] Extraire les options CLI, budgets et flags dans `Main/Options.purs`.
  `Main.purs` 183 → **180 lignes**, 101/101 tests, 110 fichiers PHP identiques
  ([bilan](audit/2026-10-20/main-options/report.md)).
- [x] Extraire la préparation des captures FFI/callbacks et du cache dans
  `Main/Prepare.purs`. `Main.purs` 180 → **146 lignes**, 101/101 tests,
  110 fichiers PHP identiques ([bilan](audit/2026-10-20/main-prepare/report.md)).
- [x] Nettoyer les imports devenus inutiles et mettre à jour les tests directs
  après les extractions ; build sans warning.

## Phase 3 — Rendre les contrats lisibles

- [x] Centraliser les noms des helpers runtime PHP (`\phpurs_*`) utilisés par
  `CodeGen`, `Printer` et `GenNativeForeign` dans `Phpurs.RuntimeNames`, avec
  un test de cohérence. 101/101 tests, 110 fichiers PHP identiques
  ([bilan](audit/2026-10-20/runtime-names/report.md)).
- [x] Faire des templates de `Printer/Runtime.purs` la source unique des
  définitions via `RuntimeNames` ; aucun littéral de nom ne reste, 101/101
  tests, 110 fichiers PHP identiques
  ([bilan](audit/2026-10-20/runtime-templates/report.md)).
- [x] Remplacer les `PhpRaw "null"` récurrents par un constructeur `PhpNull`
  nommé ; 101/101 tests, 110 fichiers PHP identiques
  ([bilan](audit/2026-10-20/php-null/report.md)).
- [x] Évaluer les autres `PhpRaw` : les noms de helpers sont centralisés, les
  chaînes restantes sont des builtins/opérateurs ou du PHP opaque. Aucun
  constructeur supplémentaire retenu.
- [x] Documenter les frontières JS/PureScript : entrées, sorties, erreurs,
  mutations et fonctions publiques dans `docs/ffi-boundaries.md`.
- [x] Couverture d’erreur FFI auditée ; les modules purs n’ont pas de contrat
  d’erreur et les erreurs FS restent exercées indirectement.
- [x] Donner à chaque module JavaScript FFI un test de son chemin d’erreur
  lorsque le module en a un. `composer-merge.mjs` couvre les manifestes
  malformés/absents et la précédence ; 102/102 tests
  ([bilan](audit/2026-10-20/ffi-errors/report.md)).
- [x] Types internes et frontières FFI documentés dans `docs/ffi-boundaries.md`
  et `docs/architecture.md`.

## Phase 4 — Tests maintenables

- [x] Extraire le cycle de vie des fixtures temporaires dans
  `tests/codegen/fixtures/cli.mjs` ; `cache-cli` et `composer-merge` migrés,
  **20/20** tests ciblés ([bilan](audit/2026-10-20/test-helpers/report.md)).
- [x] Ajouter les helpers `runPhp` et `snapshotFiles` dans
  `tests/codegen/fixtures/cli.mjs` ; migrer `cache-cli`.
- [x] Migrer `file-emission.mjs` vers `temporaryRoot` et `snapshotText`.
- [x] Migrer `file-emission`, `build-profile`, `purmeta-budget`,
  `purmeta-profile` et `rewrite-limit` vers les helpers de fixtures.
- [x] Migrer `module-cache` vers `temporaryRoot`.
- [x] Classer les 36 fichiers de tests en générateur direct, CLI/cache et
  FFI/runtime, et documenter les conventions dans `tests/codegen/README.md`.
- [x] Déplacer la famille CLI/cache dans `tests/codegen/cli/`, ajuster les
  imports et le script ; 102/102 tests.
- [x] Déplacer la famille FFI/runtime dans `tests/codegen/ffi/`, ajuster les
  imports et le script ; 102/102 tests.
- [x] Séparer les trois familles dans `tests/codegen/direct/`, `cli/` et
  `ffi/`, tout en gardant `npm run test:codegen` comme entrée unique ;
  102/102 tests.
- [x] Réduire les fixtures dupliquées et migrer les familles vers les helpers
  communs ; les helpers locaux restants portent des scénarios distincts.
- [x] Tests ciblés ajoutés ou suites existantes ajustées pour chaque extraction
  de module.
- [x] Distinguer les erreurs de sélection, de compilation, de génération PHP
  et d’exécution dans les runners ; tester leur propagation et documenter les
  commandes de reproduction (23 octobre 2026).

## Phase 5 — Documentation

- [x] Ajouter un court document d’architecture : TAST/PBO → AST PHP → printer →
  sorties → cache/manifeste → entrées modulaires/bundles dans
  `docs/architecture.md`.
- [x] Mettre à jour `docs/compiler.md` après les extractions et pointer vers
  `architecture.md`, `development.md` et `ffi-boundaries.md`.
- [x] Documenter le mode de développement recommandé dans
  `docs/development.md` : build, tests ciblés, helpers et runner benchmark.
- [x] Recenser les flags, scripts et chemins actifs. Aucun flag ou script
  obsolète actif identifié ; les mentions DCE sont historiques. Aucune
  suppression ([bilan](audit/2026-10-20/surface-inventory/report.md)).

## Revue complémentaire du 23 octobre 2026

- [x] Séparer le pipeline `CodeGen`, les expressions et les déclarations ;
  conserver ensemble les traductions récursives qui partagent l’ordre
  d’évaluation.
- [x] Extraire le scan des alias dans `ArrayRefs.Ownership` et mutualiser les
  inspections avec `ArrayRefs.Support`.
- [x] Regrouper les utilitaires communs des passes dans `Optimize.NeutralExpr`,
  `Optimize.Names` et `Optimize.ScalarWorker`, avec des exports explicites.
- [x] Aligner les chemins `GenNativeForeign` et `ComposerMerge` sur leurs noms
  de modules ; retirer les options redondantes et l’état sans lecteur.
- [x] Mutualiser le rendu des switches dans le printer et clarifier les noms
  des helpers concernés, en préservant les sorties.
- [x] Épingler les formateurs et appliquer les conventions aux sources, tests
  et outils maintenus ici. `npm run check` regroupe formatage, build et tests.
- [x] Rendre le test des callables autonome et les runners fiables sur les
  sélections invalides, les échecs des outils et l’absence de suites.
- [x] Corriger les liens et les contrats FFI dans la documentation actuelle.

Validation : build sans erreur ni avertissement ; 121 tests codegen/runner
réussis (suite complète de 114 tests, puis les 7 nouveaux tests de `modtest`) ;
5 fixtures exécutées avec le fork TAST et des caches neufs
(`FunctionFFIBoundary`, `ImmediateStateFusion`, `RecordUpdateFusion`, `TCO`,
`TCOMutRec`). Sur les mêmes 2 686 entrées TAST et sources FFI figées de b8x,
`--main Inter.Api.Main --bundle --no-cache` produit les mêmes 1 498 fichiers
PHP et les mêmes manifestes Composer et de sorties, octet pour octet.
Les 59 modules locaux ont des chemins conformes et des imports sans cycle ;
les liens locaux de la documentation actuelle sont résolus.

## Bibliothèques PHPurs — 23 octobre 2026

- [x] Revoir les 56 dépôts frères ; nettoyer les FFI, rendre leurs noms et
  responsabilités explicites, documenter les capacités des ports Node.
- [x] Retirer les captures inutilisées et le code mort prouvé ; factoriser les
  continuations Aff/Promise, les opérations HTTP et les callbacks Stream.
- [x] Retirer deux FFI obsolètes, la classe `PhpursCompose`, les closures PHP
  `mkFn1`/`runFn1`, deux types privés de Spec et le champ HTTP `sendHeaders`.
- [x] Corriger les replis EventEmitter, les finalizers retournant une Promise
  et la conservation des continuations Reader, avec des régressions PHP durables.
- [x] Remplacer les 40 runners copiés par des wrappers du runner commun
  `bin/test-package`, avec restauration de configuration, lock intact, garde
  TAST et nettoyage limité au package.
- [x] Épingler le formateur PHP, vérifier les AST avant/après formatage et
  conserver les contrats SHA des FFI spécialisées, anciens et nouveaux.
- [x] Actualiser le starter et les guides de développement ; distinguer le
  bundle historique des sources maintenues ; retirer les copies de fixtures
  FS générées et suivies par erreur.

Validation : **146 tests du compilateur et des outils**, build sans avertissement,
**132 fichiers PHP** restants valides et formatés. Les 40 runners de bibliothèques
avaient terminé avec le code zéro ; ce seul résultat ne prouvait pas leur succès.
La vérification des suppressions a confirmé Prelude et HTTP, mais révélé un arrêt
prématuré de Spec sans Revolt, déjà présent dans les premiers logs. Avec
l'autoloader de `phpurs-aff/vendor/autoload.php`, Spec termine avec **62 tests
réussis et 3 en attente**. Son runner reste à fiabiliser pour refuser une sortie
prématurée sans résumé.
Les **118 sources FFI** ont le même AST avant/après formatage ; les refactors
async, Node et core ont leurs vérifications différentielles et tests ciblés.
Les changements préexistants de configuration et de PureScript restent intacts ;
les trois FFI déjà modifiées n'ont reçu que le formatage vérifié.
Les huit répertoires FS non suivis préexistants ont été conservés.

Voir [`docs/libraries.md`](docs/libraries.md) pour reproduire les vérifications.

## Définition de terminé

- Les responsabilités de `CodeGen`, `ArrayRefs`, `Printer` et `Main` sont
  visibles dans des modules nommés, sans cycle d’imports.
- Les noms des helpers runtime et les frontières FFI ont une source unique.
- Les tests partagent leurs helpers et restent verts (voir la validation
  datée ci-dessus).
- Un nouvel humain peut suivre le pipeline et lancer les tests sans lire tout
  l’historique de performance.
