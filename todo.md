# phpurs — plan de nettoyage et de maintenabilité

> **Statut :** actif · **Dernière revue :** 31 octobre 2026 · **Portée :** ce dépôt
> (`phpurs/phpurs` : compilateur, CLI, tests, docs, outils).
> **Hors portée :** les dépôts frères `phpurs-*` et le fork PBO, sauf lorsqu’un
> contrat les traverse (chantier M7).

Ce document est le **backlog unique** de la maintenabilité. Les rapports de
`audit/` sont des archives de mesures : ils documentent le passé, ils ne
pilotent plus les priorités. Une tâche = une PR ; la case est cochée dans la PR
qui la ferme, avec la date.

## 0. Comment lire ce document

- **Priorités** : `P0` bloque le reste, `P1` nécessaire à l’objectif, `P2`
  amélioration.
- **Effort** : ordre de grandeur en jours-homme, pour un mainteneur qui connaît
  déjà le dépôt.
- **Statut** : `[ ]` à faire, `[~]` en cours, `[x]` fait, `[!]` bloqué (motif
  écrit à côté), `[-]` abandonné (décision écrite).
- Les identifiants (`M1-01`…) sont stables : ils servent de référence dans les
  PR, les issues et les ADR.
- Une tâche n’est pas terminée tant que **code + test + documentation** ne sont
  pas dans la même PR.

## 1. Objectif

Un humain qui n’a pas écrit le code doit pouvoir, sans aide :

1. **Builder et tester** un clone en moins de 15 minutes, avec une seule
   commande et un résultat non ambigu ;
2. **localiser** en moins de 10 minutes le module et la fonction responsables
   d’un comportement observé ;
3. **ajouter une passe d’optimisation** en moins d’une journée, en copiant un
   modèle documenté, sans toucher aux cinq autres passes ;
4. **modifier un contrat FFI** (représentation, arité, annulation) sans casser
   les bibliothèques sœurs, et être averti au build quand il le fait ;
5. **comprendre pourquoi** une décision existe (ordre des passes, budget, ABI,
   clé de cache) sans lire l’historique Git ni les rapports d’audit ;
6. **être arrêté par la CI** dès qu’il casse un invariant, plutôt qu’à
   l’exécution d’une application en production.

## 2. Indicateurs de réussite

Mesures de départ au 31 octobre 2026, cibles à atteindre à la fin du plan.

| Indicateur | Départ | Cible |
| --- | --- | --- |
| Modules > 400 lignes | 0 | 0 |
| Fonctions > 60 lignes | 16 | 0 hors exceptions listées |
| Modules avec en-tête `-- \|` | 81 / 81 | 81 / 81 |
| Passes avec fiche (preuve, repli, budget, test) | 11 / 11 | 11 / 11 |
| `PhpRaw ""` (sentinelle) | 0 | 0 |
| Constructions `PhpRaw` | 4 (documentées) | 0 hors exceptions documentées (≤ 4) |
| Seuils numériques hors module `Budget` | 0 (src/Phpurs) | 0 |
| Sorties PHP figées (golden) | 7 cas / 7 fichiers | 12 cas, diff octet |
| Tests exécutés | 149 | ≥ 200 |
| CI | workflow complet, pas encore exécuté | obligatoire sur `main`, < 20 min |
| Docs | 27 fichiers, 1 langue (docs), `compiler.md` 116 l. | index, hub ≤ 250 l. |
| Commits « misc » | 168 / ~176 | 0 nouveau |
| `npm run check` | local seulement | vert sur clone propre + CI |

## 3. Non-objectifs

- **Performance** : aucun nouvel audit de performance, aucune comparaison de
  temps dans les critères d’acceptation. L’archive reste
  [`audit/2026-10-20/todo-archive/performance-backlog-2026-10-20.md`](audit/2026-10-20/todo-archive/performance-backlog-2026-10-20.md).
- **Nouvelles optimisations** : ce plan ne change pas les algorithmes ; il rend
  ceux qui existent lisibles et vérifiables.
- **Réécriture du runtime PHP** ou changement d’ABI non versionné.
- **Suppression des audits historiques** ou réécriture de l’historique Git.
- **Migration de langage/outillage** (le fork PBO et `purs-tidy` restent).

## 4. Règles de travail (invariants)

Ces règles s’appliquent à toutes les tâches ; une PR qui les viole est refusée.

1. **Comportement observable préservé.** À entrée identique, la sortie PHP, les
   manifestes et les erreurs restent identiques, octet pour octet, sauf tâche
   explicitement contractuelle (M7). Le golden (M1-03) le prouve.
2. **Déplacer ≠ changer.** Une extraction ou un renommage ne modifie ni
   l’algorithme, ni l’ordre d’évaluation, ni les budgets. Un changement de
   comportement a sa propre PR.
3. **Une PR = une tâche.** Diff ≤ 400 lignes hors déplacements ; sinon découper.
4. **Tests et docs dans la PR.** Pas de « documentation dans un suivi ».
5. **Pas d’abstraction à un seul usage.** Une factorisation n’est introduite
   qu’avec au moins deux appelants réels (règle déjà en vigueur).
6. **Budgets = garde-fous**, pas des réglages de performance. Déplacer une
   constante ne change pas sa valeur ; la changer exige un test de frontière et
   une entrée d’ADR.
7. **Tout `PhpRaw` nouveau** est justifié par un commentaire et couvert par un
   test ; il ne peut pas être vide ni servir d’opérande.
8. **Documentation en une langue** (voir ADR-005) ; toute extraction met à jour
   le pointeur d’architecture dans la même PR.
9. **Commits conventionnels** : `type(scope): résumé` avec `type` ∈
   `feat|fix|refactor|docs|test|build|perf|chore` et `scope` ∈
   `passes|codegen|printer|ast|ffi|runtime|cache|cli|docs|tests|tools`.
10. **La CI est la seule autorité.** Un test vert en local ne remplace pas la
    CI ; un test instable est corrigé ou supprimé, jamais ignoré.

## 5. État de référence (31 octobre 2026)

### 5.1 Mesures

| Élément | Valeur |
| --- | --- |
| Modules PureScript | 81 (`src/`), 9 884 lignes |
| FFI JavaScript | 13 fichiers, tous documentés dans [`docs/ffi-boundaries.md`](docs/ffi-boundaries.md) |
| Plus gros modules | `CodeGen/Expr` 752, `EnumRegions` 516, `Printer/Expr` 513, `StateFusion` 432, `ArrayRefs/Analysis` 363 |
| Fonctions les plus longues | `translateSyntax` 475 l., `printExprWith` 142, `TailInline.optimize` 131, `translateWithContracts` 121, `Main.compile` 103 |
| Tests | **166 réussis** (60 direct+FFI ; 106 CLI) |
| Format | `npm run format:check` ✅ |
| Build | `spago build` ✅, 0 avertissement, 0 erreur |
| Docs | 27 fichiers, tous en anglais (hors `todo.md`) ; `compiler.md` 116 lignes |
| Modules sans en-tête | 0 / 61 |
| CI | workflow ajouté, pas encore exécuté |
| Historique | 168 commits « misc » sur ~176 |
| Audits suivis | 7,7 Mo, 10 dossiers datés |
| Scratch non suivi | `tests/runner` 432 Mo, `output/` 98 Mo, `.purmeta/` 54 Mo |

Le build et les tests ci-dessus ont été rejoués le 31 octobre 2026 sur l’arbre
courant. Note d’environnement : `spago build` échoue avec
`unable to open database file` si le cache global Spago
(`~/Library/Caches/spago-nodejs` sur macOS, emplacement XDG sur Linux) n’est pas
inscriptible ; ce n’est pas un défaut du dépôt, mais cela doit être documenté
pour la CI et les environnements sandboxés.

### 5.2 Ce qui est déjà sain — ne pas casser

- Formatage automatique et versions épinglées (`purs-tidy`, Prettier, plugin PHP).
- Frontières FFI JavaScript décrites entrée/sortie/erreur dans
  [`docs/ffi-boundaries.md`](docs/ffi-boundaries.md).
- Traversée d’AST avec frontières de portée **explicites** dans
  [`PhpAst/Traversal.purs`](src/Phpurs/PhpAst/Traversal.purs) (choix
  `AllScopes`/`LocalScope`, match exhaustif).
- Une suite de tests par passe, avec refus, replis, budgets, idempotence et
  ordre d’évaluation.
- Invariants du pipeline écrits dans [`docs/architecture.md`](docs/architecture.md).
- Zéro `unsafeCoerce`, `unsafeCrashWith`, `Debug.todo` ou `unsafePartial` dans
  `src/` (vérifié le 31 octobre 2026).
- Noms runtime centralisés dans [`RuntimeNames.purs`](src/Phpurs/RuntimeNames.purs).
- `.gitignore` couvre `output/`, `.purmeta/`, `bin/phpurs.js`, `tests/runner/*`.
- Cache de modules versionné et checksumé, avec tests de corruption.
- 146 tests et 0 avertissement de build : la base est saine, le problème est
  l’**organisation** et la **garantie** de cet état.

### 5.3 Déjà fait (ne pas refaire)

Le nettoyage d’octobre 2026 a déjà : découpé `CodeGen`, `ArrayRefs`, `Printer`
et `Main` en sous-modules ; centralisé les noms runtime ; introduit `PhpNull` ;
classé les tests en trois familles ; documenté l’architecture et les frontières
JS ; unifié les runners de bibliothèques. Voir
[`audit/2026-10-20/cleanup-closeout/report.md`](audit/2026-10-20/cleanup-closeout/report.md)
et les bilans voisins (`codegen-*`, `arrayrefs-*`, `printer-split`,
`main-*`, `runtime-*`, `test-families`).

Le présent plan reprend **après** ces extractions : il ne propose pas de
redécouper ce qui l’est déjà.

## 6. Points de friction

Chaque friction est datée et reliée à un chantier.

| ID | Friction | Preuve | Impact | Chantier |
| --- | --- | --- | --- | --- |
| F1 | Dispatch géant | `translateSyntax` : 475 lignes, 24 cas, dans un module de 752 lignes ; `printExprWith` 142 l. | Lecture et revue difficiles ; conflits de merge ; impossible de tester une famille de syntaxe isolément | M3 |
| F2 | Protocole de passes dupliqué 5× | Même garde `bindings > 256`, même collecte des groupes récursifs à un binding, même `runState { fuel: 32768 }`, mêmes préfixes ; `Result` nommé seulement dans `StateFusion` | Toute évolution (stats, budget, ordre) doit être répliquée 6 fois ; risque de divergence silencieuse | M2 |
| F3 | Budgets dispersés | Résolu le 31/10 : `nodeBudget`, `workerBudget`, `fuel`, profondeurs et largeurs vivent dans `Phpurs/Budget.purs` ; aucune passe n’en code | Le risque de divergence des seuils est supprimé | M2-03 |
| F4 | Helpers dupliqués | `require` dans `Optimize.NeutralExpr` et `EnumRegions` ; `bounded` pour `NeutralExpr` et pour `PhpExpr` ; `size` vs `countNodes` | Mêmes noms, sémantiques différentes ; unification déplacée en M3 avec les découpages | M3 |
| F5 | Invariant AST violé | Résolu le 31/10 : `PhpUnaryOp` remplace les 4 encodages `PhpBinOp "op" (PhpRaw "") expr` | Plus d’enfant fantôme ; les traversées voient un seul opérande | M4-01 |
| F6 | `PhpRaw` polymorphe | Résolu le 31/10 : 4 `PhpRaw` documentés (gardes et template Array, message d’erreur) ; appels, `$GLOBALS` et `unset` ont des constructeurs typés | La règle est écrite dans `docs/compiler.md` | M4-03 |
| F7 | Docs non maintenables | Résolu le 31/10 : docs en anglais, source map générée ; reste `compiler.md` à 595 l. | Découpe du hub en M5-02 | M5 |
| F8 | Couverture inégale | Aucun test direct de `PhpAst.Traversal`, `Printer`, des sous-modules `CodeGen.*` et `ArrayRefs.*`, de `Main.*`, `BuildCache`, `BuildInputs`, `OutputManifest`, `FileEmission` | Les contrats critiques ne sont vérifiés qu’indirectement, par chance | M6 |
| F9 | CI non activée | Workflow ajouté, aucun run ; pas de protection de branche | Rien n’empêche encore un merge rouge tant que la CI n’est pas requise | M1 |
| F10 | Dépendance PBO par chemin relatif | Résolu le 31/10 : `tools/toolchain.json`, `check:toolchain`, `bin/setup` clone la révision épinglée | Reste à valider par le premier run CI | M8-02 |
| F11 | Hygiène du dépôt | Résolu le 31/10 : `CONTRIBUTING`, `CHANGELOG`, `engines`, `prepublishOnly`, `npm run clean`, rétention des audits ; reste Nix (M8-05) | Release outillée | M8 |
| F12 | Décisions non écrites | Résolu le 31/10 : ADR-001 à 007 dans `docs/decisions/` | Le « pourquoi » est consultable | M9 |

## 7. Chantiers

### M1 — Garde-fous reproductibles (P0)

Objectif : rendre l’état « vert » vérifiable par une machine, sur chaque PR.

- [!] **M1-01** — CI `check` (P0, 1–2 j). _Bloqué : exécution GitHub._
  `.github/workflows/check.yml` : Node 22, PHP 8.4, `npm ci`, checkout du fork
  PBO au chemin attendu à une révision épinglée (dans le workflow d’abord, puis
  dans `tools/toolchain.json` en M8-02), puis `npm run check`
  (format → build + bundle → tests). Cache npm et cache Spago (chemin selon
  l’OS). Journaux et répertoires temporaires publiés en cas d’échec.
  _Fait (31/10) : workflow ajouté, layout miroir
  (`phpurs/phpurs` + `purescript-backend-optimizer-phpurs`), caches npm/Spago,
  `check:toolchain`. Reste : premier run sur GitHub, puis M1-08 (protection de
  branche)._
- [x] **M1-02** — `npm run check` complet (P0, 0,5 j).
  Ajouter `check:conventions`, `check:docs`, `test:golden` au fur et à mesure
  que M1-03/M1-04/M1-05 existent. Les scripts ne doivent jamais masquer un code
  de sortie (pas de `| tail` dans les scripts npm ; `set -o pipefail` dans les
  scripts bash).
  _Fait (31/10) : `check:conventions`, `check:docs`, `check:shell` et
  `test:golden` branchés dans `npm run check` ; aucun script ne masque un code
  de sortie (`set -euo pipefail` dans les entrées shell)._
- [x] **M1-03** — Corpus golden (P0, 2 j).
  `tests/golden/cases/*` (entrées CoreFn/FFI minimales) et
  `tests/golden/expected/*` (PHP + manifestes). 12 cas : ADT simple, région
  scalaire, constructeur nullable, record + update, TCO, Aff, FFI, modular,
  bundle, cache froid, cache chaud, sans main. `npm run test:golden` compare
  octet pour octet ; `npm run golden:update` régénère pour une PR qui change
  volontairement la sortie.
  _Fait (31/10) : harnais `tests/golden/run.mjs` (diff ligne à ligne, détection
  des fichiers orphelins) et **12 cas / 13 fichiers** : `adt`, `tco`, `records`,
  `strings`, `numbers`, `arrays`, `branches`, `nullable`, `ffi`, `runtime`
  (Aff/promise), `entrypoints` et `bundle`. Cache froid/chaud et manifestes
  restent couverts par les tests CLI (`module-cache.mjs`, `cache-cli.mjs`,
  `package-runner.mjs`) plutôt que par un rendu pur._
- [x] **M1-04** — `tools/check-conventions.mjs` (P0, 1,5 j).
  Vérifie : en-tête `-- |` sur chaque module ; module ≤ 400 lignes ; fonction
  ≤ 60 lignes ; matrice d’imports autorisés par couche ; `PhpRaw ""` interdit ;
  seuils numériques interdits hors `Phpurs.Budget` (mode avertissement avant
  M2-03, bloquant ensuite). Sortie `fichier:ligne: règle: message`.
  _Fait (31/10) : en-têtes, tailles, sentinelles, imports, règle
  « aucun seuil numérique hors `Phpurs.Budget` » et baseline ratchet
  (`tools/conventions-baseline.json`). Dette restante : 4 modules et
  16 fonctions ; en-têtes et sentinelles à zéro._
- [x] **M1-05** — `tools/check-docs.mjs` (P1, 1 j). _Fait (31/10)._
  `check:docs` vérifie liens, ancres, index (avertissement jusqu’à M5-02),
  taille du hub `compiler.md` (avertissement) et mélange de langues._
  Liens relatifs et ancres valides, `docs/index.md` à jour (créé en M5-02),
  `compiler.md` ≤ 250 lignes, pas de mélange de langues dans un même document.
- [x] **M1-06** — Shellcheck (P2, 0,5 j). _Fait (31/10)._
  `tools/check-shell.sh` : `bash -n` sur les 5 entrées, `shellcheck -x` quand
  il est présent, `SHELLCHECK_REQUIRED=1` en CI._
  `shellcheck -x bin/*` et `bash -n` en CI ; corriger les scripts concernés.
- [x] **M1-07** — Templates GitHub (P1, 0,5 j). _Fait (31/10)._
  Template de PR + issues « pass bug », « FFI contract », « documentation »._
  PR (checklist : invariant, golden, test, doc, ADR si décision), issues
  (bug de passe, contrat FFI, documentation), `CODEOWNERS` si plusieurs
  mainteneurs.
- [!] **M1-08** — Protection de branche (P1, 0,2 j, manuel). _Bloqué : réglage GitHub._
  Checks requis (`check`), revue obligatoire, squash merge, message de commit
  conventionnel.

**Acceptation** : une PR ouvre la CI en moins de 20 minutes ; un module de
450 lignes, un module sans en-tête ou une sortie PHP modifiée échouent ; le
résultat est reproductible sur un clone propre.

### M2 — Protocole de passes unifié et budgets centralisés (P0)

Objectif : une passe = un module court, un contrat explicite, un budget nommé.
Le protocole `Result` unifié couvre les passes qui transforment le TAST
(6 passes) ; les passes qui transforment l’AST PHP (`TailInline`,
`Nullable.lower`, `CopyCleanup`, `ArrayRefs`, `CompactLoops`) gardent leur
signature mais reçoivent la même fiche et les mêmes budgets.

- [x] **M2-01** — Module `Phpurs.Pass` (P0, 1,5 j). _Fait (31/10)._
  `Phpurs.Pass` fournit `Result`, `Pass`, `unchanged`, `smallModule`
  (256 bindings / 64 par groupe), `scanGroups` (budget de scan 8 192),
  `scanNodes` et `privateName`. `PartialBindings` migré sans changement de
  sortie ; test direct `tests/codegen/direct/pass-protocol.mjs`.
  `type Result r = { module_ :: BackendModule, privateNames :: Set Ident | r }`,
  `type Pass r = BackendModule -> Result r`, `unchanged`, `guardModule`
  (seuils documentés : 256 bindings, 64 par groupe, 128 dataDecls, 64
  constructeurs/champs), `scanBindings` (traverse les groupes, applique le
  seuil de scan), `copyWorker` (préfixe `__phpurs_*`, nom privé, recopie),
  `freshPrefix`.
- [x] **M2-02** — Migrer les 4 passes restantes (P0, 2 j). _Fait (31/10)._
  `ThunkFusion`, `StateFusion`, `CallbackSpecialization`, `ArrayCallbacks`
  utilisent `smallModule`, `scanGroups` et `privateName` ; `EnumRegions`
  conserve son résultat étendu et utilise `scanGroupsWithin` (son compteur
  `size <= nodeBudget` est préservé) + `privateName`. Aucun changement de
  sortie : golden identique, 147 tests verts. Baseline resserrée
  (`EnumRegions` 516→511, `StateFusion` 432→422).
- [x] **M2-03** — `Phpurs.Budget` (P0, 1 j).
  Constantes nommées et justifiées : `proofNodes` (1024), `scanNodes` (8192),
  `stateFuel` (32768), `workerLimit` (32), `moduleBindings` (256),
  `groupBindings` (64), `dataDecls` (128), `depth`, `width`. Supprimer les
  littéraux `8192`, `32768`, `256`, `64` des passes ; fusionner
  `require`/`bounded`/`size`/`countNodes` derrière des noms distincts
  (`require` pour `Maybe Unit`, `withinNodes` pour `NeutralExpr`,
  `withinPhpNodes` pour `PhpExpr`, `nodeCount` unique).
  _Fait (31/10) : `Phpurs.Budget` couvre les passes TAST, `NullableConstructors`,
  `CopyCleanup`, `TailInline`, `CompactLoops` et le helper `bounded` ; aucun
  littéral de budget ne reste hors de `Phpurs/Budget.purs` dans `src/Phpurs`.
  _Fait (31/10, suite)_ : `requireCheck`, `regionNodeCount` et `boundedPhp`
  nomment les variantes locales ; `require` et `bounded` documentent leur
  portée dans `Optimize.NeutralExpr`.
- [x] **M2-04** — Pipeline tabulaire + statistiques (P1, 2 j). _Fait (31/10)._
  `CodeGen/Pipeline` : table des 6 passes TAST (`name`, `precondition`,
  `budget`, `fallback`, `run`), `run` ordonné, `PassStats` agrégé et
  `translateWithStats`. La sortie `--verbose` affiche
  `passes: proved/unchanged/refused`. Les refus (budget/preuve) restent à 0
  tant qu’une passe ne les expose pas ; `translateWithStats` (131 l.) est
  l’exception de fonction documentée.
- [x] **M2-05** — Documenter le protocole (P1, 1 j). _Fait (31/10)._
  `docs/compiler/passes.md` : fiches des 11 passes (preuve, budgets, repli,
  test), liées depuis `compiler.md`._
  `docs/compiler/passes.md` : cycle de vie d’une passe, invariants, recette
  « ajouter une passe », recette « tester un refus », et une fiche pour chacune
  des 11 passes (y compris les 5 passes sur l’AST PHP).

**Acceptation** : ajouter une passe = un module d’environ 30 lignes plus une
entrée dans la table ; sortie PHP identique (golden) ; `PassStats` explique
pourquoi une passe ne s’applique pas à un module.

### M3 — Découpage ciblé (P1)

Objectif : aucun module au-dessus de 400 lignes, aucune fonction au-dessus de
60 lignes, sauf exception écrite.

- [x] **M3-01** — `CodeGen/Expr` (P1, 3 j). _Fait (31/10)._
  `Expr/Support` (169 l.), `Terms` (270 l.), `Statements` (304 l.),
  `Prims` (151 l.), `Update` (144 l.) ; la façade `Expr` fait **80 lignes**
  et `translateSyntax` **37 lignes** (473 → 37, cible ≤ 40). Toutes les
  fonctions de famille sont ≤ 80 lignes (max `translateLetRec` 72). Golden
  et 149 tests identiques. `translateBranch` (69) et `tryFusedUpdate` (68)
  restent > 60 et sont dans la baseline.
- [x] **M3-02** — `Printer/Expr` (P1, 2 j). _Fait (31/10)._
  `Printer/Context` (18 l.), `Names` (50 l., FFI déplacée), `Support`
  (164 l. : captures, returns, switch, `printCall` paramétré par un renderer)
  et `Expr` (353 l.). `printExprWith` : 143 → **78 lignes** ; golden et
  149 tests identiques.
- [x] **M3-03** — `EnumRegions` (P1, 2 j). _Fait (31/10)._
  `EnumRegions/Types` (47 l.), `Proof` (inference + worker, 223 l.),
  `Rewrite` (context, scan, optimize, 303 l.) et une façade de 12 lignes.
  `regionNodeCount` vit dans `Types`. Sortie identique (golden `adt`).
- [x] **M3-04** — `StateFusion` (P1, 1,5 j). _Fait (31/10)._
  `StateFusion/Types` (13 l.), `Prove` (239 l.), `Rewrite` (174 l.) et une
  façade `StateFusion` (45 l.) qui garde `Result`, `optimize` et
  `workerBudget`. Sortie identique (golden, 149 tests).
- [x] **M3-05** — `TailInline` (P2, 1 j). _Fait (31/10)._
  `TailInline/Proof` (114 l. : nœuds, captures, `leafNode`, `terminates`,
  `flattenCall`) et `TailInline/Rewrite` (126 l. : `simplifyReturns`,
  `mapLocals`, `rename`, `compactCopies`). La façade `TailInline` (161 l.)
  garde `optimize` (129 l., exception existante) et les budgets.
- [x] **M3-06** — `ArrayRefs/Analysis` (P1, 1,5 j). _Fait (31/10)._
  `ArrayRefs/CallSites` (89 l.), `Decision` (200 l.), `Fixpoint` (130 l.) ;
  `Analysis` (44 l.) agrège les 7 entrées publiques. `docs/architecture.md`
  pointe vers les trois modules.
- [x] **M3-07** — Exceptions de taille (P1, 0,2 j). _Fait (31/10)._
  `docs/conventions.md` ne liste **aucune** exception de module depuis
  M3-01. Les exceptions de fonction sont dans la baseline.

**Acceptation** : `check:conventions` vert ; golden identique ; les pointeurs
de `docs/architecture.md` et la source map générée pointent vers les nouveaux
modules.

### M4 — AST PHP saine (P1)

Objectif : l’AST ne contient plus de nœuds fantômes ni de convention implicite.

- [x] **M4-01** — `PhpUnaryOp` (P1, 1 j). _Fait (31/10)._
  `PhpUnaryOp String PhpExpr` remplace les 4 encodages
  `PhpBinOp "op" (PhpRaw "") expr`. `Traversal`, `Printer/Expr`,
  `CodeGen/Operators`, `CopyCleanup`, `TailInline`, `CompactLoops` et
  `ArrayRefs/Ownership` traitent le nouveau nœud. Sortie imprimée inchangée
  (`( ! $x)`, test `unary-operators.mjs`).
- [x] **M4-02** — Invariants testés (P1, 0,5 j). _Fait (31/10)._
  `PhpRaw ""` a disparu de `src/` ; `check:conventions` le refuse et la dette
  correspondante est retirée de la baseline. Le test `unary-operators.mjs`
  vérifie le nœud, l’enfant unique, la traversée et l’impression. Reste : un
  test global « aucune AST générée n’imprime `/* ERROR */` » (M6).
- [x] **M4-03** — Classer les `PhpRaw` (P1, 1,5 j). _Fait (31/10)._
  `PhpCall (PhpRaw "\\phpurs_*")` → `PhpDirectCall` pour un global généré ou
  `PhpNamedCall` pour un nom de fonction littéral (`count`, `\intdiv`,
  `\fmod`, helpers runtime) ; `$GLOBALS[...]` → `PhpGlobalSlot` ;
  `unset(...)` → `PhpUnset`. Restent 4 `PhpRaw` documentés : deux gardes de
  traversal Array, le template `foreach` Array et le message d’erreur généré.
  Règle écrite dans `docs/compiler.md`.
- [x] **M4-04** — Exhaustivité de `Traversal` (P1, 1 j). _Fait (31/10)._
  `tests/codegen/direct/ast-traversal.mjs` compare la table d’échantillons aux
  constructeurs exportés (41 formes), vérifie `mapChildren` identité, le
  mapping de chaque enfant direct et les frontières `localChildren`.
- [-] **M4-05** — (optionnel) `newtype PhpName`/`PhpNamespace` (P2, 2 j). _Abandonné (31/10)._
  M4-03 a introduit `PhpNamedCall`, `PhpDirectCall` et `PhpGlobalSlot`, qui
  lèvent les ambiguïtés nom/expression ; un nouveau type ne justifie pas la
  migration.

**Acceptation** : `grep 'PhpRaw ""'` = 0 ; ≤ 4 `PhpRaw` documentés ; test
d’exhaustivité ; sortie identique.

### M5 — Documentation maintenable (P1)

Objectif : une seule source de vérité par sujet, une langue, des liens testés.

- [x] **M5-01** — Langue et plan (P1, 3 j). _Fait (31/10)._
  ADR-005 : anglais pour `README.md` et `docs/`, français pour `todo.md`.
  `architecture.md`, `development.md` et `ffi-boundaries.md` traduits ; le
  paragraphe français restant de `compiler.md` l’est aussi.
- [x] **M5-02** — Découper `compiler.md` (P1, 3 j). _Fait (31/10)._
  `docs/compiler/{pipeline,lowering,printing,emission,diagnostics}.md` + hub
  `compiler.md` (116 lignes) + `docs/index.md` global. Le README reste le hub
  applicatif (27 Ko) ; sa découpe en `getting-started.md` est reportée à M5-02b
  si nécessaire.
- [x] **M5-03** — Source map générée (P1, 2 j). _Fait (31/10)._
  `tools/docs-source-map.mjs` génère `docs/compiler/source-map.md` depuis les
  en-têtes ; `check:source-map` échoue si la page est périmée. La table manuelle
  de `compiler.md` est remplacée par le lien (642 → 595 lignes).
  En-têtes de modules normalisés (`-- | Responsabilité. Invariants. Budgets.`),
  `tools/docs-source-map.mjs` produit la table des modules et vérifie en CI
  (`--check`). Supprimer la table manuelle de `compiler.md`.
- [x] **M5-04** — En-têtes manquants (P1, 0,5 j). _Fait (31/10)._
  14 en-têtes ajoutés ; la baseline d’en-têtes est vide (61/61)._
  Les 14 modules sans en-tête (liste mesurée : `CompactLoops`, `NativeCallbacks`,
  `StateFusion`, `NullableConstructors`, `ArrayRefs`, `EnumRegions`,
  `CopyCleanup`, `ForeignTraversals`, `ThunkFusion`, `CallbackSpecialization`,
  `ArrayCallbacks`, `TailInline`, `PartialBindings`, `Metrics`).
- [x] **M5-05** — ADR (P1, 1 j). _Fait (31/10)._
  `docs/decisions/` : index, template et ADR-001 à 007._
  `docs/decisions/` + template (contexte, décision, alternatives, conséquences,
  date, statut) + ADR-001 à 007 (voir M9).
- [x] **M5-06** — `CONTRIBUTING.md` (P1, 1,5 j). _Fait (31/10)._
  Setup, layout, recettes (passe, budget, AST, FFI, golden, déplacement) et
  règles de PR.
  Setup (dont le fork PBO), gates, conventions de commit, checklist de revue,
  recettes : ajouter une passe, ajouter un helper runtime, modifier l’ABI FFI,
  ajouter un golden, mettre à jour la doc.
- [x] **M5-07** — `docs/conventions.md` (P1, 1 j). _Fait (31/10)._
  Tailles, noms, `PhpRaw`, budgets, tests, docs, commits, exceptions._
  Tailles, nommage (dont `module_`, imposé par PBO), `PhpRaw`, budgets, tests,
  langue, liens, exceptions.

**Acceptation** : `check:docs` vert ; `compiler.md` ≤ 250 lignes ; 59/59 modules
avec en-tête ; un nouveau contributeur suit `CONTRIBUTING` sans question.

### M6 — Tests des contrats (P1)

Objectif : chaque contrat critique a un test nommé et une panne explicite.

- [x] **M6-01** — Matrice passe → contrats (P1, 1 j). _Fait (31/10)._
  Table des 11 passes dans `tests/codegen/README.md` (preuve, repli, budgets,
  test), plus `pass-protocol.mjs` et `ast-traversal.mjs`.
  Dans `tests/codegen/README.md` : pour chaque passe, les contrats couverts
  (preuve, repli, budget, idempotence, ordre d’évaluation, erreurs) et le
  fichier de test correspondant. Les cases vides deviennent des tâches.
- [x] **M6-02** — Comboler les trous (P1, 5 j). _Fait (31/10)._
  Couverture consolidée : `Traversal` (`ast-traversal`), `Printer` (golden +
  `unary-callables`), `CodeGen.{Tco,Bindings,Effects,Declarations,Types,
  Operators}` (`tco-direct`, `recursive-initialization`, `effect-results`,
  `adt`, `exact-arity`, `unary-operators`), `ArrayRefs.{Analysis,Ownership,
  Rebuild,Support}` (`array-refs`, `array-refs-ownership`), `Main.Options`
  (`cli/options.mjs`), `BuildCache`/`BuildInputs`/`OutputManifest`/
  `FileEmission`/`Metrics` (`module-cache`, `cache-cli`, `file-emission`,
  `build-profile`, `ast-metrics`).
- [x] **M6-03** — Tests de propriété `fast-check` (P1, 2 j). _Fait (31/10)._
  `direct/properties.mjs` : 5 suites (identité `mapChildren`, monotonie des
  free vars, composition du renommage, clés de cache déterministes/sensibles,
  imprimante sans `ERROR`/`Impossible` et parenthèses équilibrées).
- [x] **M6-04** — Golden étendu (P1, 1 j). _Fait (31/10)._
  Cas ajoutés : `nullable`, `ffi`, `runtime` (Aff/promise), `entrypoints`,
  `bundle`. Manifestes et cache froid/chaud restent couverts par les tests CLI
  (`package-runner.mjs`, `module-cache.mjs`, `cache-cli.mjs`).
- [x] **M6-05** — Couverture mesurée (P2, 1 j). _Fait (31/10)._
  `npm run test:coverage` (`c8`) : **98,64 %** de statements, 89,87 % de
  branches. Étape CI non bloquante ; cible 70 % largement dépassée.
- [x] **M6-06** — Smoke TAST (P2, 2 j). _Fait (31/10)._
  `npm run test:tast-smoke` détecte le fork (`../../purescript` ou
  `PHPURS_TAST_PURS`) et les paquets `phpurs-*`, puis lance `bin/test` sur une
  fixture. Skip explicite et motivé quand ils manquent ; étape CI séparée et
  non bloquante.
- [-] **M6-07** — Messages d’échec normalisés (P2, 0,5 j). _Abandonné (31/10)._
  Les tests récents nomment déjà la fixture et le contrat ; une passe globale
  sur 159 tests apporterait peu. Convention consignée dans
  `tests/codegen/README.md` ; à rouvrir si un échec CI reste opaque.

**Acceptation** : matrice remplie à 100 % ; 5 suites de propriétés ; trous
fermés ou waiver daté.

### M7 — Contrats FFI (P1/P2)

Objectif : les frontières PHP sont des contrats écrits, validés au build et
testés, pas des conventions découvertes à l’exécution. Le détail des constats
est en [annexe A](#annexe-a--backlog-ffi-constats-b8x).

- [x] **M7-01** — Forme du fichier FFI (P1, 1 j). _Décidé (31/10)._
  ADR-002 : garder le fragment inline, documenter `$exports`/`__DIR__`, tracer
  la voie « vrai fichier PHP ». Les diagnostics d’export relèvent de M7-04.
  Décider : garder le fragment inline (recommandé à court terme) et documenter
  portée de `$exports`, fusion de deux `require`, valeurs brutes autorisées ;
  tracer la trajectoire « vrai fichier PHP » dans l’ADR-002.
- [x] **M7-02** — Racine de module (P1, 1 j). _Fait (31/10)._
  `runtime.php` définit `PHPURS_ROOT` et `phpurs_root()` (résolus depuis
  `__DIR__` du fichier runtime) ; sémantique du layout généré documentée dans
  `docs/ffi-boundaries.md` et testée par `tests/codegen/ffi/runtime-preamble.mjs`.
- [x] **M7-03** — Forme de `$exports` (P1, 1,5 j). _Fait (31/10)._
  `GenNativeForeign.validate` analyse le fragment : clés = `foreign import`,
  arité des fonctions = signature aplatie, clé inconnue rejetée. Messages
  `Module.clé: expected N parameters, found M` / `missing|unknown PHP FFI
  export 'clé'`. Les exports conditionnels restent dynamiques ; un fragment
  sans clé commune est traité comme source partagée.
- [x] **M7-04** — Validation des exports au build (P1, 2 j). _Fait (31/10)._
  Les erreurs remontent dans `TranslatedModule.foreignErrors` et `Main` échoue
  avant toute émission. `declare(strict_types=1)` et `namespace` sont refusés
  dans un fragment. Tests : `export-validation.mjs` (clé, arité, interdit),
  `missing-ffi.mjs` (export inconnu = build en échec) et `cache-cli.mjs`.
  Clé manquante et arité incohérente détectées **avant** l’exécution, y compris
  pour une application partielle au top-level (`_parseParisTime`). Diagnostic
  clair pour `declare(strict_types=1)` ou `namespace` dans un fragment.
  À planifier tôt : c’est le correctif FFI au meilleur rapport valeur/risque.
- [x] **M7-05** — `phpurs_apply` et callbacks (P1, 1 j). _Fait (31/10)._
  Helper runtime `phpurs_apply($fn, array $args)` : vérifie le nombre de
  paramètres déclarés (variadiques exemptés) et lève `TypeError` avec les
  comptes attendus/reçus. Documenté dans `docs/ffi-contract.md`, testé dans
  `runtime-preamble.mjs` (succès et arité incorrecte).
- [x] **M7-06** — ABI runtime versionnée (P1, 3 j). _Fait (31/10)._
  `docs/ffi-contract.md` : ABI v1, `PHPURS_ABI_VERSION`/`phpurs_abi_version()`,
  accesseurs `phpurs_variant_tag/value` et `phpurs_record_get`, formes
  `Maybe`/`Either`/`Effect`/`EffectFnAff`, callbacks et `$exports`. Tests dans
  `runtime-preamble.mjs`. Le source runtime entre dans l’empreinte backend de
  la clé de cache ; un changement d’ABI l’invalide.
- [x] **M7-07** — Aff (P1, 2 j). _Fait (31/10)._
  `phpurs_aff_from_operation` (thenable rejouable, `then` = nouvelle opération)
  et `phpurs_non_cancellable` (canceler idempotent) dans le runtime ;
  `docs/ffi-contract.md` documente replay, annulation et échec ; tests PHP
  replay (deux `then`), annulation double et `$reject`. Aucun `$runAsync`
  recopié ne subsiste dans le dépôt.
- [-] **M7-08** — Distribution des helpers de test (P2, 2 j). _Abandonné (31/10)._
  Aucun test ne fait de `require` en dur vers un checkout voisin : les
  fixtures chargent le runtime en mémoire (`tests/codegen/fixtures/runtime.mjs`)
  et les exécutions PHP utilisent `vendor/autoload.php` du projet généré. Un
  paquet `phpurs/test-support` n’aurait aucun consommateur ; à rouvrir si un
  projet externe en a besoin.
- [x] **M7-09** — Figer la documentation (P1, 1 j). _Fait (31/10)._
  `docs/ffi-contract.md` créé et lié depuis `docs/ffi-boundaries.md` et
  `docs/libraries.md` ; `check:docs` : 29 pages, 0 erreur.

**Acceptation** : b8x peut supprimer ses contournements ; un export manquant
échoue au build ; l’ABI a une version, des accesseurs et des tests.

### M8 — Dépôt et outillage (P2)

- [x] **M8-01** — Paquet npm (P2, 1 j). _Fait (31/10)._
  `engines`, `prepublishOnly`, `CHANGELOG.md` ; `npm pack --dry-run` vérifié
  (129 fichiers, sans caches temporaires ni `.github`).
  `engines` aligné sur la version de CI (Node 22), npm ≥ 10, `files` explicite
  ou `.npmignore` vérifié, `prepublishOnly: npm run check`, version semver,
  `CHANGELOG.md` (Keep a Changelog).
- [x] **M8-02** — `bin/setup` + PBO (P1, 1 j). _Fait (31/10)._
  `tools/toolchain.json` épingle dépôt, chemin et révision ; `bin/setup` clone
  le fork à cette révision ; `tools/check-toolchain.mjs` compare le checkout et
  le chemin `spago.yaml`, et échoue avec un message clair (utilisé par la CI).
- [x] **M8-03** — `npm run clean` (P2, 0,5 j). _Fait (31/10)._
  `tools/clean.mjs` (garde de chemin, `--dry-run`), documenté dans
  `development.md` ; scratch `.tmp-*` ignoré par Git et npm.
  Supprime `output`, `.spago`, `.purmeta`, `tests/runner/*`, `bin/phpurs.js`
  avec garde de chemin et confirmation ; documenté.
- [x] **M8-04** — Rétention des audits (P2, 0,5 j). _Fait (31/10)._
  Politique ajoutée en tête de `audit/README.md` : rapports immuables, pas de
  `raw/`, revue à chaque release, archivage > 6 mois.
  Politique : rapports immuables, pas de `raw/`, revue à chaque release,
  archivage au-delà de 6 mois dans un tag ; `audit/README.md` mis à jour.
- [!] **M8-05** — Nix (P2, 1 j). _Bloqué : `nix` absent de l’environnement._
  Vérifier `nix build` et `nix develop` ; job CI non bloquant ; documenter.
- [x] **M8-06** — Convention de commit (P2, 0,5 j). _Fait (31/10)._
  `tools/check-commit-msg.mjs`, script `check:commit`, étape CI sur le titre de
  PR, règle écrite dans `docs/conventions.md`._
  Conventional Commits + lint du message en CI ; portées fermées.
- [x] **M8-07** — Fins de ligne (P2, 0,2 j). _Fait (31/10)._
  `git ls-files --eol` : tout le texte est en LF, seuls les PNG sont binaires ;
  règle documentée dans `docs/conventions.md`.
  Vérifier `.gitattributes` et `.editorconfig` ; documenter la règle.
- [x] **M8-08** — Templates d’issue (P2, 0,3 j). _Fait (31/10)._
  Voir M1-07 : trois modèles d’issue dans `.github/ISSUE_TEMPLATE/`._
  Voir M1-07.

### M9 — Décisions écrites (P1)

Objectif : le « pourquoi » devient consultable et stable.

- [x] **M9-01** — ADR-001 : passes pures `BackendModule -> Result`, sans IO,
  sans mutation du TAST.
- [x] **M9-02** — ADR-002 : FFI = fragment inline ; `$exports`, `__DIR__`,
  valeurs brutes ; trajectoire « fichier PHP ».
- [x] **M9-03** — ADR-003 : ABI runtime versionnée, accesseurs stables,
  politique de rupture.
- [x] **M9-04** — ADR-004 : budgets = garde-fous ; valeurs et justification ;
  procédure pour les changer.
- [x] **M9-05** — ADR-005 : langue et structure des docs ; source map générée.
- [x] **M9-06** — ADR-006 : la clé de cache inclut le flux de modules
  précédent ; raison et conséquences.
- [x] **M9-07** — ADR-007 : `EffectFnAff` rejouable, annulation coopérative,
  sémantique PHP.
- [x] **M9-08** — Revue trimestrielle (P2, récurrent, 0,5 j). _Première revue (31/10)._
  Journal dans `docs/decisions/README.md` : ADR relus, budgets centralisés,
  3 exceptions de module et 16 de fonction suivies par M3. Prochaine revue :
  2027-01-31.

## 8. Séquencement

Charge totale estimée : **≈ 75–85 j-h**. Calendrier : environ **8 semaines pour
deux contributeurs à temps plein** (les chantiers M5/M8/M9 sont parallélisables),
ou **4 à 5 mois pour un mainteneur seul à mi-temps**.

| Jalon | Semaine | Contenu | Critère de sortie |
| --- | --- | --- | --- |
| **J0 — Socle** | S1 | M1-01…M1-04, M5-04, M5-05 (template) | CI verte sur une PR de test ; golden et conventions bloquants |
| **J1 — Passes** | S1–S2 | M2-01…M2-03, M4-01, M4-02 | 6 passes TAST migrées, budgets centralisés, sortie identique |
| **J2 — Taille** | S2–S4 | M3-01…M3-04, M3-06, M4-03, M4-04 | Aucun module > 400 l. hors exceptions ; AST sans sentinelle |
| **J3 — Preuves** | S4–S5 | M6-01…M6-04, M1-05, M5-01, M5-02 | Matrice de tests remplie ; docs en une langue ; hub ≤ 250 l. |
| **J4 — Contrats** | S5–S6 | M7-01…M7-05, M7-09, M5-03, M5-06 | Validation FFI au build ; source map générée |
| **J5 — ABI et release** | S6–S8 | M7-06…M7-08, M8-01…M8-06, M5-07, M9 | ABI documentée et testée ; `npm run check` vert sur clone propre ; CHANGELOG ; tag |

Chemin critique : `M1-03 → M1-04 → M2 → M4 → M3 → M6 → M7-06`.
Parallélisable sans risque : M5 (docs), M8 (outillage), M9 (ADR).

### Premières PR, dans l’ordre

1. `build(ci)` : workflow + `npm run check` (M1-01, M1-02).
2. `test(golden)` : 3 cas d’abord (ADT, TCO, records), puis 12 (M1-03).
3. `build(tools)` : `check-conventions` + baseline ratchet (M1-04).
4. `docs(modules)` : 14 en-têtes + template ADR (M5-04, M5-05).
5. `refactor(passes)` : `Phpurs.Pass`, migration de `PartialBindings` seule (M2-01).
6. `refactor(passes)` : les 4 autres passes (M2-02).
7. `refactor(passes)` : `Phpurs.Budget` (M2-03).
8. `refactor(ast)` : `PhpUnaryOp` + suppression des sentinelles (M4-01, M4-02).
9. `refactor(codegen)` : extraction d’une famille de `translateSyntax` (M3-01).
10. `refactor(printer)` : extraction d’une famille de `printExprWith` (M3-02).
11. `test(ast)` : exhaustivité de `Traversal` (M4-04).
12. `docs(compiler)` : hub + première sous-page (M5-02).

Chaque PR coche sa case ici et met à jour la source map.

## 9. Risques

| Risque | Prob. | Impact | Mitigation |
| --- | --- | --- | --- |
| Le golden ne couvre pas assez de sorties | Moyenne | Élevé | 12 cas + comparaison de la sortie de référence b8x (2 686 entrées, 1 498 fichiers) à chaque release |
| Un découpage crée des cycles d’imports | Moyenne | Moyen | Matrice de couches (M1-04) + revue avant extraction |
| Déplacer un budget change la sortie | Faible | Élevé | Valeur inchangée, test de frontière, golden, ADR-004 |
| La traduction des docs introduit des erreurs | Moyenne | Faible | Revue bilingue, `check:docs`, terminologie dans `conventions.md` |
| La CI est lente ou coûteuse | Moyenne | Moyen | Cache npm/Spago, jobs séparés, tests ciblés par famille |
| Une version d’ABI casse les bibliothèques sœurs | Faible | Élevé | Version d’ABI + `bin/modtest` + période de double support |
| Dérive vers la performance | Moyenne | Moyen | Non-objectifs explicites ; refus en revue ; archive consultable |
| Bus factor 1 | Élevée | Élevé | `CONTRIBUTING`, ADR, revue externe, ce document à jour |

## 10. Définition de terminé

Le plan est terminé quand, sur un clone propre :

- [ ] `npm ci && npm run check` passe en moins de 15 minutes, sans variable
      d’environnement cachée (hors fork PBO et PHP) ;
- [ ] la CI est requise sur `main` et reproductible (même résultat sur deux
      machines) ;
- [ ] aucun module ne dépasse 400 lignes hors exceptions listées et datées ;
- [ ] chaque module a un en-tête, chaque passe a une fiche, chaque décision
      structurante a un ADR ;
- [ ] les 12 golden sont stables et une variation de sortie est détectée ;
- [ ] `PhpRaw ""` a disparu, les `PhpRaw` restants sont classés et testés ;
- [ ] les budgets vivent dans un seul module, avec une justification ;
- [ ] la matrice de tests est complète, les trous fermés ou waivés ;
- [ ] les contrats FFI (exports, arité, `__DIR__`, ABI, Aff, replay) sont
      écrits, validés au build et testés ;
- [ ] la documentation est monolingue, indexée, à liens vérifiés, et
      `compiler.md` tient en un hub ;
- [ ] `CHANGELOG.md`, `CONTRIBUTING.md` et le semver existent, le paquet npm est
      publiable ;
- [ ] un nouveau contributeur a réalisé une PR de bout en bout sans aide.

## Annexe A — Backlog FFI (constats b8x)

Constats issus de la revue des 32 fichiers `.php` écrits à la main de b8x,
filtrés pour ne garder que ce qui révèle un contrat ou un manque **phpurs**.
Les correctifs applicatifs (cookie, hash, configs, `Encode`, `Clean`, tests
b8x, `php -l`) sont hors périmètre. Chaque point est repris par une tâche M7.

### A.1 Forme du fichier FFI : un fragment, pas un fichier PHP

- Le fichier est inliné dans une closure de module : `declare(strict_types=1)`,
  `namespace`, classes et fonctions nommées sont impossibles ; `$exports` est
  initialisé par le wrapper, pas par le fichier. Deux `require` dans la même
  portée fusionnent les exports (constaté).
- `__DIR__` désigne l’emplacement généré, pas `src/` : b8x code le layout
  `run/bak/php/output` dans `Path.php` pour retrouver sa racine.
- `$exports` peut contenir des valeurs brutes (`empty`,
  `_rootDirAbsolutePath`), pas seulement des fonctions ; `Auth.php` retourne un
  tableau littéral hors contrat et fonctionne quand même.
- Appeler une fonction PureScript depuis PHP (`$encodeJson($v)`, `$just($ms)`,
  `$mimeTypeToExtension($m)`) dépend de l’uncurrying décidé par l’optimiseur :
  si la fonction reste curryfiée, l’appel retourne une closure silencieusement.

**Décision recommandée** : garder le fragment, écrire le contrat, ajouter
`phpurs_root()` et les diagnostics de build (M7-01, M7-02, M7-05).

### A.2 Représentations runtime exposées au PHP

- Le PHP connaît `->type`/`->value` (Variant, dans `Router.php` et
  `TaggedSum.php`), les records comme `stdClass`, `Maybe`/`Either` comme
  callbacks `$just/$nothing/$left/$right`. C’est un ABI de fait, non documenté.
- `Effect` = closure zéro-argument ; `EffectFnAff` = closure à deux callbacks
  retournant un canceler ; `Unit` = valeur libre.
- Un `EffectFnAff` doit être **rejouable** (b8x teste « Hash could not
  replay ») : contrat implicite, jamais écrit.

**Décision recommandée** : accesseurs stables + version d’ABI + tests de
stabilité (M7-06).

### A.3 Aff / EffectFnAff — le point `$runAsync`

- Aucun helper PHP pour fabriquer un `EffectFnAff` depuis une opération
  sync/async : chaque application réécrit le même `$runAsync` (Postgres, S3,
  Ses, RabbitMq) et les mêmes cancelers, avec des sémantiques divergentes —
  certains abandonnent l’opération à l’annulation, d’autres la laissent courir.
- Le seul exemple du contrat est en **JavaScript**, dans le commentaire de
  `phpurs-aff/src/Effect/Aff/Compat.purs` ; aucun équivalent PHP documenté.
- Pas de notion « non annulable » standard : b8x réimplémente
  `$onCancelSuccess(null)` à chaque fois, et `Projector.php` documente que
  l’annulation ne libère pas le verrou.
- `phpurs_promise` existe dans le runtime (thenable JS-like) mais n’est ni
  utilisé ni documenté pour ce cas.

**Décision recommandée** : `phpurs_aff_from_operation` +
`phpurs_non_cancellable` + contrat de replay, testés (M7-07).

### A.4 Validation au build

- Une clé `$exports` manquante n’échoue qu’à la demande ; avec une application
  partielle au top-level (`parseParisTime = _parseParisTime Just Nothing`),
  cela fait échouer l’**initialisation du module** — cas `_parseParisTime`
  dans b8x.
- Les fragments contenant `declare(strict_types=1)` ou `namespace` échouent au
  parse silencieusement à l’inlining : le diagnostic doit être explicite.

**Décision recommandée** : validation clé+arité au build, diagnostic dédié
(M7-04).

### A.5 Distribution / outillage

- `test/ffi/effects.php` de b8x `require` en dur
  `../../phpurs/phpurs-effect/src/Effect/Uncurried.php` (checkout voisin) pour
  obtenir `runEffectFn1/2` : fournir un paquet Composer ou un helper de test
  supporté.
- Les tests FFI d’une application ne peuvent pas instancier
  `Effect`/`EffectFnAff` sans recopier les conventions : fournir des doubles
  (`phpurs_test_*`).

**Décision recommandée** : paquet `phpurs/test-support` + doubles (M7-08).

### A.6 Documentation

`docs/libraries.md` doit figer : inlining et portée de `$exports`, sémantique
de `__DIR__`, représentations record/Variant/Maybe/Effect, convention
`EffectFnAff` + annulation + replay, callbacks PureScript uncurried, et les
valeurs brutes autorisées dans `$exports`.

### A.7 Cas d’école

`_parseParisTime` manquant dans `Instant.php`, détecté seulement à
l’initialisation du module généré → argument pour la validation au build
(§A.4, M7-04).

## Annexe B — Commandes de référence

```sh
# Gate complet (format, build + bundle, tests)
npm run check

# Tests seuls, par famille
npm run test:codegen
node --test tests/codegen/direct/enum-regions.mjs
node --test tests/codegen/cli/cache-cli.mjs
node --test tests/codegen/ffi/missing-ffi.mjs

# Golden et conventions (après M1)
npm run test:golden
npm run golden:update
npm run check:conventions
npm run check:docs

# Mesures rapides
find src -name '*.purs' | wc -l
find src -name '*.purs' -exec wc -l {} + | sort -rn | head -20
node --test --test-concurrency=1 tests/codegen/direct/*.mjs \
  tests/codegen/ffi/*.mjs
node --test --test-concurrency=1 tests/codegen/cli/*.mjs

# Build isolé
npm run build     # spago build && node tools/bundle.mjs
```

Notes d’environnement :

- `spago build` a besoin d’un cache global inscriptible
  (`~/Library/Caches/spago-nodejs` sur macOS, emplacement XDG sur Linux). Dans
  un environnement sandboxé, utiliser un `HOME` local au workspace pour les
  mesures, sans modifier le dépôt.
- Les tests CLI utilisent `bin/phpurs.js` ; après une modification de `src`,
  relancer `npm run build` avant `npm run test:codegen`.
- `bin/test` et `bin/modtest` exigent le fork TAST et les dépôts frères ; ils
  ne font pas partie du gate `check`.

## Annexe C — Modèles

### C.1 Fiche de passe (à placer en tête du module)

```text
-- | <Nom> — <responsabilité en une phrase>.
-- |
-- | Précondition : <ce qui doit être vrai pour tenter la passe>
-- | Preuve      : <ce que la passe démontre avant de réécrire>
-- | Budget      : <constantes importées de Phpurs.Budget>
-- | Repli       : <ce qui est laissé intact quand la preuve échoue>
-- | Test        : <fichier de test, contrats couverts>
```

### C.2 ADR (gabarit)

```markdown
# ADR-00N — Titre

- Statut : proposé | accepté | remplacé
- Date : AAAA-MM-JJ
- Contexte : ...
- Décision : ...
- Alternatives : ...
- Conséquences : ...
- Références : tâche(s) Mx-yy, tests, PR
```

### C.3 Checklist de PR

- [ ] La tâche `Mx-yy` est référencée et cochée dans ce document.
- [ ] Le comportement observable est inchangé (golden), ou le changement est
      explicitement contractuel et documenté.
- [ ] Les tests couvrent le contrat modifié (nom du test = contrat).
- [ ] Les budgets n’ont pas changé, ou l’ADR est mis à jour.
- [ ] La documentation (module, `docs/`, source map) est à jour.
- [ ] `npm run check` est vert ; la CI est verte.
- [ ] Le commit suit `type(scope): résumé`.
