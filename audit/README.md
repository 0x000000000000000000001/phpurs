# Audit — index des bilans

Index des rapports historiques et décisions de performance. Le statut est
indicatif ; chaque rapport fait foi. Les procédures reproductibles restent
dans les dossiers d’audit correspondants.

Le backlog de performance complet est archivé dans
[`2026-10-20/todo-archive/performance-backlog-2026-10-20.md`](2026-10-20/todo-archive/performance-backlog-2026-10-20.md).

## 2026-09-08

| Rapport | Statut | Sujet |
| --- | --- | --- |
| [LazyEvaluation : coût des captures et limite FFI](2026-09-08/lazy/report.md) | Intégré | Le profil identifie l'allocation et la destruction des closures capturantes comme première piste. Une représentation par objet invocable divise pre… |
| [R0 : réutilisation des variables terminales de branches](2026-09-08/r0/report.md) | Intégré | La première optimisation est intégrée à PHPurs. Le PHP de `balance` passe de **249 à 33 variables distinctes**. Une comparaison avec le snapshot av… |
| [R10 intégré : couleurs scalaires dans les régions fermées](2026-09-08/r10-integrated/report.md) | Intégré | La passe est intégrée au générateur PHPurs. `altbak.pub-phpurs/bin/php/run -c` passe et les 301 modules produits correspondent exactement à ceux du… |
| [R8 — Contrat des fonctions aux frontières FFI](2026-09-08/r8-boundary/report.md) | Intégré | La fixture `tests/passing/FunctionFFIBoundary` passe en **PHP 8.5.4 et en JavaScript** avec le compilateur actuel. Elle conserve une même fonction… |
| [R8 — Captures compactes intégrées aux boucles éligibles](2026-09-08/r8-integrated/report.md) | Intégré | **La première transformation générique est intégrée dans PHPurs et le PHP actif a été régénéré.** LazyEvaluation prend **49,540–49,886 ms dans les… |
| [R8 — Conversion de la racine à la sortie de buildThunks](2026-09-08/r8-root/report.md) | Intégré | **L'expérience conserve le gain des captures compactes et le contrat public testé. Elle n'est pas encore intégrée au compilateur.** Sur la copie PH… |
| [R9 — Inlining terminal intégré](2026-09-08/r9/report.md) | Intégré | Le backend régénère désormais un PHP qui supprime les deux appels terminaux de `ins` à `balance`, sans traitement spécifique à RBTree. Les 14 résul… |
| [Profil RBTree — 8 septembre 2026](2026-09-08/rbtree/report.md) | Mesuré / historique | **Premier chantier : réduire les variables locales générées dans les branches exclusives de `balance`.** Une variante qui ne change que leurs noms… |
| [RBTree après R0 : profil et inlining diagnostique](2026-09-08/rbtree-after-r0/report.md) | Mesuré / historique | Le coût massif de libération des variables locales observé avant R0 a disparu du profil natif. La meilleure nouvelle variante mesurée réduit RBTree… |
| [R10 — Couleurs internes et ADT publics conservés](2026-09-08/r10-boundary/report.md) | Prototype / non intégré | **La fixture et le prototype passent.** Une copie de RBTree conservant les fonctions et ADT publics donne **253–261 ms au total**, contre **304–310… |
| [RBTree après R9 : coût de la représentation des ADT](2026-09-08/rbtree-after-r9/report.md) | Prototype / non intégré | **Une copie diagnostique ramène RBTree à 156–160 ms et le total des 14 benchmarks à 223–227 ms**, contre respectivement 240–242 ms et 307–308 ms po… |

## 2026-09-09

| Rapport | Statut | Sujet |
| --- | --- | --- |
| [Nettoyage des copies internes : intégration et mesures](2026-09-09/copy-cleanup/report.md) | Intégré | La passe `Phpurs.CopyCleanup` est intégrée après l’inlining terminal et l’abaissement nullable. `altbak.pub-phpurs/bin/php/run -c` réussit. Le back… |
| [R14 — Fusion des thunks à profondeur dynamique](2026-09-09/dynamic-thunks/report.md) | Intégré | Le 9 septembre 2026. Extension intégrée dans `src/Phpurs/ThunkFusion.purs`, validée avec `altbak.pub-phpurs/bin/php/run -c`. |
| [R13 — réutiliser les applications partielles internes](2026-09-09/partial-bindings/report.md) | Intégré | La passe `Phpurs.PartialBindings` est intégrée au générateur, avant la fusion des thunks et les régions ADT. Sur le point d'entrée normal de la sui… |
| [RBTree : arbre vide nullable et tests de nullité](2026-09-09/r10-null/report.md) | Intégré | Le prototype sur copies ramène **RBTree de 171–173 à 138–140 ms** et le **total de 190–193 à 157–159 ms**, soit **16,67–18,30 % de temps total en m… |
| [Représentation nullable privée : intégration R10](2026-09-09/r10-null-integrated/report.md) | Intégré | La transformation est activée dans PHPurs. `altbak.pub-phpurs/bin/php/run -c` réussit et les sorties actives correspondent au générateur intégré. D… |
| [R11 : fusion des chaînes immédiatement forcées, intégrée](2026-09-09/r11-integrated/report.md) | Intégré | La passe `Phpurs.ThunkFusion` transforme le TAST optimisé avant TCO et génération PHP. Elle est activée dans le générateur normal ; `altbak.pub-php… |
| [Lazy Evaluation : prototype de fusion construction/évaluation](2026-09-09/lazy-fusion/report.md) | Mesuré / historique | Le prototype isolé supprime la matérialisation de la chaîne immédiatement forcée. **Il n'est pas intégré au générateur.** Les 301 modules PHP actif… |

## 2026-09-24

| Rapport | Statut | Sujet |
| --- | --- | --- |
| [Tag privé supprimé : intégration et mesures](2026-09-24/private-tag/report.md) | Intégré | La passe `EnumRegions` émet des constructeurs privés dont la propriété `$tag` |
| [RBTree : comptage des allocations et borne supérieure de la réutilisation en place](2026-09-24/rbtree-alloc/report.md) | Mesuré / historique | Reconnaissance avant transformation : les comptages historiques sont |

## 2026-10-01

| Rapport | Statut | Sujet |
| --- | --- | --- |
| [B1 — Trois états de recompilation du backend](2026-10-01/build-scenarios/report.md) | Mesuré / historique | Référence PHPurs : checkout propre au commit `3471ae4c13de854018a7ff8a0819a84de8162a3f`, après le lot des écritures conditionnelles. PBO : `0f41544… |
| [B1 — Clé de cache de module, v1](2026-10-01/cache-key/report.md) | Mesuré / historique | Base PHPurs : `415487f7838e9db8ffd1a876b3bc8c2558c18f7d`. PBO : `0f41544464ec0f42e6cb0dd77b206852813f904f`. Le lot précédent de mesure M0 fournit l… |
| [B3 — Contrat d'émission et collecte Composer](2026-10-01/composer-emission/report.md) | Mesuré / historique | Référence : commit PHPurs `a84133d`. Comparaison avec le cleanup appliqué dans le checkout, le 1er octobre 2026. |
| [B3 — Éviter les recherches FFI des modules sans foreign](2026-10-01/ffi-empty/report.md) | Mesuré / historique | Référence : commit PHPurs `de27407`, après le cleanup Composer. Comparaison avec la garde `Map.isEmpty backendMod.foreign` ajoutée dans `Main`, le… |
| [B1 — Sauvegarde et restauration de l'état de module](2026-10-01/module-state/report.md) | Mesuré / historique | Base PHPurs : `6c1f89a44cb8550bfae3b0e62c43b28dfc6bb944`. PBO : `0f41544464ec0f42e6cb0dd77b206852813f904f`, sans modification dans ce lot. Validati… |
| [B3 — Racines partagées et candidats FFI dédupliqués](2026-10-01/package-paths/report.md) | Mesuré / historique | Référence : checkout PHPurs après le lot sans foreign, sur le commit `de27407`. La référence inclut sa garde non commitée ; son bundle est identifi… |
| [B3 — Émission bundle seul](2026-10-01/bundle-only/report.md) | Rapport | Référence : checkout PHPurs après le lot des chemins, sur le commit `de27407` avec les lots précédents non commités. Le bundle de référence est ide… |
| [B1 — Préserver les fichiers PHP identiques](2026-10-01/write-if-changed/report.md) | Rapport | Référence : checkout PHPurs propre au commit `b2ec0bf1662119458c358a7350ab2e6fed2abfd3`, incluant le lot bundle seul. Validation du 1er octobre 2026. |

## 2026-10-02

| Rapport | Statut | Sujet |
| --- | --- | --- |
| [B1 — Activation du cache dans la CLI](2026-10-02/cache-activation/report.md) | Intégré | Base de départ PHPurs : `890652a9675f8ab8a3b368364ea338c0c6e5fb7b`. PBO : `0f41544464ec0f42e6cb0dd77b206852813f904f`, sans changement dans ce lot.… |
| [B2 — Contrat de stockage et invalidation des `.purmeta`](2026-10-02/purmeta-contract/report.md) | Intégré | Le contrat PHPurs décrit le chemin, le format actuel et les conditions de lecture. Le contrat bas niveau est également présent dans `purescript-bac… |
| [Nettoyage PHPurs et préparation Composer — 2 octobre 2026](2026-10-02/composer-preparation/report.md) | Mesuré / historique | Suppression des espaces temporaires du dépôt `phpurs/phpurs`, après vérification |
| [B2 — Configurer le budget LRU depuis PHPurs](2026-10-02/purmeta-budget/report.md) | Mesuré / historique | Base PHPurs : `7398caf734d3ee2b28434bb6d002f9cd0ff73380`. Base PBO : `0f41544464ec0f42e6cb0dd77b206852813f904f`, avec l'instrumentation du lot préc… |
| [B2 — Comparer les budgets LRU](2026-10-02/purmeta-budget-comparison/report.md) | Mesuré / historique | La campagne compare **16, 64 et 128 Mio**, avec **trois répétitions par budget**. Le budget historique de 64 Mio sert de témoin. Une compilation pr… |
| [B2 — Mesurer le cache mémoire PBO existant](2026-10-02/purmeta-profile/report.md) | Mesuré / historique | Base PHPurs : `a7b414aff7578a779446b61df9fb7fe6c0a2ef14`. Base PBO : `0f41544464ec0f42e6cb0dd77b206852813f904f`. Le bundle instrumenté porte la SHA… |
| [B1 — Suppressions de modules et changements de main](2026-10-02/cache-lifecycle/report.md) | Rapport | Base PHPurs : `7d473e72b18b32c665d85ea96daff1bfbad7ef1e`. PBO : `0f41544464ec0f42e6cb0dd77b206852813f904f`. Le bundle de ce lot porte la SHA-256 `9… |

## 2026-10-03

| Rapport | Statut | Sujet |
| --- | --- | --- |
| [B4 — réutilisation de l'analyse des variables libres](2026-10-03/free-vars/report.md) | Intégré | Date : 3 octobre 2026. Quatrième micro-étape B4, après l'accumulateur linéaire. |
| [B4 — accumulation linéaire des opérandes](2026-10-03/linear-accumulator/report.md) | Intégré | Date : 3 octobre 2026. Troisième micro-étape B4. |
| [B4 — comptage AST complet et limite de réécriture effective](2026-10-03/ast-rewrite-limit/report.md) | Mesuré / historique | Date : 3 octobre 2026. Deuxième micro-étape B4. |
| [B4 — première étape : profil des phases et logs optionnels](2026-10-03/build-profile/report.md) | Mesuré / historique | `--profile-build` ajoute un relevé JSON v1 sur stderr, avec durée totale, métriques |

## 2026-10-05

| Rapport | Statut | Sujet |
| --- | --- | --- |
| [R14 — Seeds lisant une capture scalaire pure](2026-10-05/scalar-seeds/report.md) | Intégré | Le 5 octobre 2026. Micro-étape intégrée dans |
| [R14 — Potentiel des chaînes State immédiatement exécutées](2026-10-05/state-chains/report.md) | Intégré | Le 5 octobre 2026. **Étape de mesure terminée ; prototypes sur copies de PHP.** |
| [R14 — Potentiel de spécialisation des callbacks de parcours](2026-10-05/traversal-callbacks/report.md) | Intégré | Le 5 octobre 2026. **Mesures terminées sur des copies du PHP généré.** Le coût |
| [B4 — comparaison des limites de réécriture](2026-10-05/rewrite-tradeoff/report.md) | Mesuré / historique | Date : 5 octobre 2026. Dernière micro-étape B4. |

## 2026-10-06

| Rapport | Statut | Sujet |
| --- | --- | --- |
| [R14 — Spécialisation intégrée des parcours FFI d’Array](2026-10-06/array-callbacks/report.md) | Intégré | Le 6 octobre 2026. **ArrayOps isolé : 98–100 → 13,7–13,8 µs**, soit |
| [R14 — Spécialisation intégrée des callbacks de folds TAST](2026-10-06/callback-specialization/report.md) | Intégré | Le 6 octobre 2026. **ListOps isolé : 146–148 → 65,8–66,2 µs**, soit environ |
| [M1 — Erreurs explicites pour les FFI absentes](2026-10-06/missing-ffi/report.md) | Intégré | Le générateur remplace l'objet invocable factice des FFI absentes par une |
| [R14 — Intégration des chaînes State immédiatement exécutées](2026-10-06/state-fusion/report.md) | Intégré | Le 6 octobre 2026. **Passe `StateFusion` intégrée au générateur**, après |

## 2026-10-19

| Rapport | Statut | Sujet |
| --- | --- | --- |
| [R1 — Premier corps natif à arité exacte](2026-10-19/exact-arity/report.md) | Intégré | Le 19 octobre 2026. **Intégré et mesuré sur le TAST des benchmarks.** Les |
| [R5 — Potentiel de fusion des mises à jour de records](2026-10-19/record-updates/report.md) | Intégré | Le 19 octobre 2026. **Mesure sur copie, pas encore intégrée au générateur.** |
| [R2 — Inventaire des formes explicitement uncurried](2026-10-19/uncurried-callbacks/report.md) | Mesuré / historique | Le 19 octobre 2026. **Aucune des charges nommées par R2 ne contient de |
| [R1 — Extension FFI mesurée puis refusée](2026-10-19/ffi-exact-arity/report.md) | Refusé / annulé | Le 19 octobre 2026. Le corps exact a été étendu aux wrappers FFI scalaires de |

## 2026-10-20

| Rapport | Statut | Sujet |
| --- | --- | --- |
| [R6 — Clés de singletons nullaires](2026-10-20/nullary-constructors/report.md) | Intégré | testée**. La réutilisation du singleton par `CtorSaturated` a été mesurée mais |
| [R7 — Baseline benchmark après intégration](2026-10-20/r7-baseline/report.md) | Intégré | R7 courante (108 modules, `runtime.php` partagé, 110 fichiers PHP, |
| [R7 — Runtime partagé qualifié](2026-10-20/shared-runtime/report.md) | Intégré | dans un `runtime.php` global unique, appelé avec des noms qualifiés `\phpurs_*`. |
| [R4 — Supprimer les copies de paramètres TCO](2026-10-20/tco-copies/report.md) | Intégré | boucles TCO bufferisées passent leurs paramètres par un temporaire |
| [R7 — Fermeture des bundles à main explicite](2026-10-20/bundle-reachability/report.md) | Mesuré / historique | fermeture réelle** que `App/main.mod.php` : **108 modules**, dans le même ordre. Le |
| [R3 — Inventaire des enveloppes Effect](2026-10-20/effect-wrappers/report.md) | Mesuré / historique | fichiers PHP, `runtime.php` partagé. |
| [R7 — Filtrage des modules avant optimisation](2026-10-20/module-filtering/report.md) | Mesuré / historique | l’union des fermetures d’imports des racines `main` avant `buildModules`. Seuls |
| [RBTree — profil après R4/R7](2026-10-20/rbtree-profile/report.md) | Mesuré / historique | modification du générateur. Xdebug n’est pas installé dans PHP 8.5.4 ; le profil |
| [R7 — Préambule runtime sélectif](2026-10-20/runtime-preamble/report.md) | Mesuré / historique | **306 modules** : il pesait **8 159 octets**, dont **4 693 octets** de classes |
| [R7 — DCE des bindings annulée](2026-10-20/dead-bindings/report.md) | Refusé / annulé | sont référencées ni par `$GLOBALS`, ni par un appel direct dans le PHP généré ; |
| [Hygiène du dépôt — artefacts et paquet npm](2026-10-20/repo-hygiene/report.md) | Rapport | Inventaire des artefacts ignorés et réduction du paquet npm de 10,57 Mo à 748 Ko. |
| [Inventaire des imports, exports et frontières FFI](2026-10-20/import-inventory/report.md) | Rapport | 480 imports inspectés, aucun import explicite inutilisé détecté ; frontières FFI sans export supplémentaire. |
| [Chemin de build unique](2026-10-20/build-path/report.md) | Intégré | `bin/php/run -c` utilise désormais `tools/bundle.mjs`, avec cache et identité de toolchain ; 14 benchmarks validés. |
| [Cartographie de CodeGen](2026-10-20/codegen-map/report.md) | Rapport | Découpage en types, TCO, Effect, bindings et pipeline ; extraction Types puis Tco proposée. |
| [Extraction de CodeGen.Context](2026-10-20/codegen-context/report.md) | Intégré | Types et contexte de traduction déplacés ; 110 fichiers PHP identiques, 101/101 tests. |
| [Extraction de CodeGen.Tco](2026-10-20/codegen-tco/report.md) | Intégré | Helpers de boucle TCO déplacés ; 110 fichiers PHP identiques, 101/101 tests. |
| [Extraction de CodeGen.Effects](2026-10-20/codegen-effects/report.md) | Intégré | Classification/exécution des effets déplacée ; 110 fichiers PHP identiques, 101/101 tests. |
| [Extraction de CodeGen.Bindings](2026-10-20/codegen-bindings/report.md) | Intégré | Analyse des bindings déplacée ; 110 fichiers PHP identiques, 101/101 tests. |
| [Découpage d’ArrayRefs](2026-10-20/arrayrefs-rebuild/report.md) | Intégré | Réécriture des reconstructions isolée ; 110 fichiers PHP identiques, 101/101 tests. |
| [Extraction de ArrayRefs.Analysis](2026-10-20/arrayrefs-analysis/report.md) | Intégré | Analyse d’ownership isolée ; 110 fichiers PHP identiques, 101/101 tests. |
| [Découpage du printer](2026-10-20/printer-split/report.md) | Intégré | Façade, expressions et déclarations séparées ; 110 fichiers PHP identiques, 101/101 tests. |
| [Extraction de Main.Selection](2026-10-20/main-selection/report.md) | Intégré | Sélection des mains et fermeture CoreFn isolées ; 110 fichiers PHP identiques, 101/101 tests. |
| [Extraction de Main.Outputs](2026-10-20/main-outputs/report.md) | Intégré | Finalisation des sorties isolée ; 110 fichiers PHP identiques, 101/101 tests. |
| [Extraction de Main.Options](2026-10-20/main-options/report.md) | Intégré | Parsing CLI et budgets isolés ; 110 fichiers PHP identiques, 101/101 tests. |

