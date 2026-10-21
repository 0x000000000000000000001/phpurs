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

## État au 20 octobre 2026

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
- [ ] Extraire la traduction des branches et des jonctions dans un module dédié
  si la séparation précédente réduit réellement les dépendances.
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
- [ ] Extraire la préparation des captures FFI/callbacks et du cache de
  `Main.purs`.
- [ ] Après chaque extraction, supprimer les imports devenus inutiles et
  mettre à jour les tests directs.

## Phase 3 — Rendre les contrats lisibles

- [ ] Centraliser les noms des helpers runtime PHP (`\phpurs_*`) et supprimer
  les chaînes dupliquées entre générateur, printer et tests.
- [ ] Remplacer les `PhpRaw` récurrents par des constructeurs d’AST nommés ;
  conserver `PhpRaw` pour le PHP réellement opaque.
- [ ] Documenter les frontières JS/PureScript : entrées, sorties, erreurs,
  mutations et fonctions publiques.
- [ ] Donner à chaque module JavaScript FFI un test de son chemin d’erreur.
- [ ] Vérifier qu’aucun type de données interne ne traverse une frontière
  publique sans conversion nommée.

## Phase 4 — Tests maintenables

- [ ] Factoriser les helpers communs des tests codegen : fixtures temporaires,
  lancement CLI, exécution PHP et attentes de fichiers.
- [ ] Séparer les tests du générateur direct, de la CLI/cache et des contrats
  FFI, tout en gardant `npm run test:codegen` comme entrée unique.
- [ ] Réduire les fixtures dupliquées lorsqu’elles ne prouvent pas un contrat
  distinct.
- [ ] Ajouter un test ciblé pour chaque extraction de module ; ne pas réécrire
  les tests existants sans raison.
- [ ] Rendre les échecs lisibles : message, fixture, commande de reproduction.

## Phase 5 — Documentation

- [ ] Ajouter un court document d’architecture : TAST/PBO → AST PHP → printer →
  sorties → cache/manifeste → entrées modulaires/bundles.
- [ ] Mettre à jour `docs/compiler.md` après chaque extraction et déplacer les
  détails historiques vers les audits.
- [ ] Documenter le mode de développement recommandé : `npm run build`,
  `npm run test:codegen`, tests ciblés, et quand utiliser le runner benchmark.
- [ ] Marquer les flags, scripts et chemins obsolètes ; les supprimer seulement
  après preuve d’absence d’utilisation.

## Définition de terminé

- Les responsabilités de `CodeGen`, `ArrayRefs`, `Printer` et `Main` sont
  visibles dans des modules nommés, sans cycle d’imports.
- Les noms des helpers runtime et les frontières FFI ont une source unique.
- Les tests partagent leurs helpers et restent verts (**101/101**).
- Un nouvel humain peut suivre le pipeline et lancer les tests sans lire tout
  l’historique de performance.
