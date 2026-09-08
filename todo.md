# PHPurs — performances et petites étapes

Audit du 8 septembre 2026. Ce fichier est le nouveau backlog de performance ; aucun ancien todo.md n'était présent dans ce dépôt. Les pistes viennent de la lecture du générateur, de PBO et du PHP produit. Le [profil RBTree](audit/2026-09-08/rbtree/report.md) identifie le premier coût ; [R0 est maintenant intégré et mesuré](audit/2026-09-08/r0/report.md). Le [profil LazyEvaluation](audit/2026-09-08/lazy/report.md) est terminé : les captures des closures dominent ; le prototype d'objets invocables a été retiré à cause du contrat FFI actuel (R8). [R9 est intégré et mesuré](audit/2026-09-08/r9/report.md) : total 355–359 ms après régénération, contre 400–410 ms avec la passe désactivée.

## Espace de travail

| Dépôt | Dossier depuis htdocs | Branche de travail | Tronc |
| --- | --- | --- | --- |
| Benchmarks | altbak.pub-phpurs | edge-phpurs | edge |
| PBO | purescript-backend-optimizer-phpurs | edge-phpurs | edge |
| Backend PHP | phpurs/phpurs, checkout existant | main | main |

Les deux worktrees sont des worktrees Git ordinaires persistants. Ils peuvent rester en place après rebase/merge. Les checkouts principaux de PBO et altbak.pub ne sont pas utilisés pour ce chantier.

- PHPurs pointe désormais vers ../../purescript-backend-optimizer-phpurs dans spago.yaml et spago.lock.
- altbak.pub-phpurs conserve les dépendances PHP dans ../phpurs et son backend dans ../phpurs/phpurs/bin/phpurs.
- Le node_modules des outils a été copié dans le worktree ; Composer et les sorties de compilation sont locaux au worktree. Le cache OPcache est run/bak/php/opcache, ignoré par Git.
- bin/php/run -c sélectionne explicitement le compilateur standard local pour reconstruire l'outil JavaScript PHPurs, puis le fork TAST pour le programme PHP. Validation faite avec ~/.local/bin/purs en tête du PATH appelant ; voir le bilan R0.
- Les paquets phpurs-* et le compilateur purs restent partagés, conformément au périmètre choisi. Isoler un paquet avant d'y ouvrir un chantier concurrent.
- Les corrections OpMod et d'initialisation des dictionnaires de cette session sont conservées dans PHPurs, avec leur régression RecursiveDictionaryInitialization.

Commande de validation de l'espace :

    cd /Users/0x1/Documents/htdocs/altbak.pub-phpurs
    bin/php/run -c

## Référence et limites des mesures

La commande complète réussit : code 0 et 14 benchmarks terminés. Le relevé initial affiche **767,73 ms**, somme des meilleurs temps de chaque test, et **22,30 s** de durée de processus pour reconstruction + génération + Composer + exécution. Ces deux nombres ne mesurent pas la même chose.

Les résultats attendus, temps individuels, versions, commits, état JIT et deux exécutions supplémentaires sont conservés dans [baseline.json](audit/2026-09-08/baseline.json), avec les journaux voisins. Le checkout PHPurs contient des changements non commités : l'empreinte du diff figure dans le relevé.

Limites : machine partagée avec d'autres agents ; minimum de dix itérations par test, pas médiane ; cas très courts proches de la résolution de l'horloge ; directives PBO spécifiques aux benchmarks actives. Aucun pourcentage de gain ne sera annoncé sur cette seule référence.

Dans ce premier relevé, RBTree représente 650,016 ms et LazyEvaluation 99,481 ms, soit environ 97,6 % de la somme. RBTree est maintenant profilé : la multiplication des variables locales de balance est le premier coût identifié (R0). LazyEvaluation est aussi profilé : allocation et destruction des captures dominent, sans passage du collecteur de cycles. Après retrait du prototype Lazy, le relevé donnait RBTree 286,562 ms et LazyEvaluation 101,390 ms, sur une somme de 406,653 ms. Après R9, les contrôles donnent RBTree 239–240 ms et LazyEvaluation 98–100 ms, pour un total de 355–359 ms.

## Ordre recommandé

Priorité : dégrossir avec les gros gains démontrés, puis refaire le classement des coûts.

1. R0 terminé pour la première optimisation. Le [nouveau profil après R0](audit/2026-09-08/rbtree-after-r0/report.md) écarte le coût massif des variables locales et mesure les coûts restants.
2. R9 intégré : simplification des retours et inlining terminal borné. Le PHP régénéré donne RBTree 239–240 ms contre 285–292 ms ; total 355–359 ms contre 400–410 ms avec la passe désactivée. Les deux ordres, les dix fixtures et les comptes d'allocations sont vérifiés.
3. Profil LazyEvaluation terminé : le prototype vers 50 ms change le contrat FFI et reste retiré. La fixture de frontière R8 passe en PHP/JS ; les TypeApp et types structurels aux usages sont vérifiés. Prochain essai : captures compactes dans une chaîne, conversion en Closure uniquement de la racine retournée, avec comptage et mesure avant intégration. RBTree reste le premier coût ; les enveloppes d'appels seules et le GC ne donnent pas un gros gain dans les expériences actuelles.
4. B1–B4 : accélérer la boucle de développement, avec mesures de compilation séparées.

Le TAST v3 est une donnée de départ : ann.type, dataDecls, classDecls et TypeApp portent les types et leurs instanciations. PHPurs exploite déjà dataDecls et certains types primitifs. Mobiliser ces informations pour les signatures, appels et représentations quand une expérience montre un gain important ; un audit général du TAST ne bloque pas R0. Comparer aussi les mesures aux baselines historiques du README d'altbak.pub.

Les chronos actuels sont déjà pris dans l'exécution de Bench.purs : ils conviennent aux comparaisons runtime. Les enrichissements M0 sont facultatifs et ne bloquent pas R0. Prendre les régressions M1 pertinentes avant chaque optimisation, sans imposer tout M1 comme préalable.

Une petite étape = un changement révisable, une régression ciblée, une mesure avant/après. Ne pas cumuler plusieurs optimisations avant de mesurer. Aucun changement de charge ou de résultat attendu pour faire baisser un temps.

## M0 — P2 — Enrichir le protocole de mesure existant

Constat : Bench.php utilise microtime(true), appelé à l'intérieur du protocole de Bench.purs, qui conserve le minimum de dix passages. Ce chrono exclut bien le build et Composer, même si bin/php/run orchestre aussi ces phases. PBO contient des directives Test.Polymorphism.*, Test.RBTree.* et Test.LazyEvaluation.* dans Directives/Defaults.purs, lignes 40–60. Le nom « 10M Type Class Dict Lookups » ne garantit donc pas que dix millions de dispatchs survivent à l'optimisation.

- [ ] Ajouter un export JSON des résultats et de toutes les durées, sans changer le calcul ni les entrées des tests.
- [ ] Passer l'horloge PHP à hrtime(true), en conservant l'unité microseconde à la frontière Bench.
- [ ] Séparer les commandes : rebuild compilateur, purs, backend PHP, préparation Composer, exécution seule. Chronométrer chaque phase.
- [ ] Enregistrer minimum, médiane, dispersion, mémoire maximale, taille du PHP, versions, commits et statut effectif JIT/OPcache.
- [ ] Ajouter trois états de build : sans changement, modification d'une feuille, modification d'une dépendance. Garder les benchmarks runtime indépendants de ces états.
- [ ] Conserver un profil « historique » et un profil « général » sans directives nommant Test.* ; inspecter le PHP pour vérifier ce que mesure chaque cas.
- [ ] Ajouter des tailles paramétrables et un minimum de répétitions pour les tests de quelques microsecondes ; vérifier les sorties à chaque échantillon.
- [x] Profiler RBTree : appels Xdebug, échantillonnage natif sous JIT et variantes isolées avec le chrono existant. Voir le rapport et R0.
- [x] Profiler LazyEvaluation : appels Xdebug, échantillonnage natif avec JIT, variantes isolées et vérification FFI. Voir R8 et le rapport.

Validation : les 14 résultats restent identiques. Rapporter démarrage froid, exécution chaude et compilation dans des colonnes séparées. Utiliser un profiler avec JIT désactivé pour localiser les coûts, puis remesurer sans instrumentation avec les paramètres de production.

## M1 — P0 — Verrouiller les contrats avant de supprimer des coûts

Constat : CodeGen.purs, ligne 625, traduit encore PrimEffect en chaîne TODO_PrimEffect ; les sémantiques PBO activées peuvent produire les primitives ST new/read/write. Main.purs, lignes 137–143, remplace une FFI manquante par un objet invocable factice. Le runner exclut notamment une frontière Int32 et un cas d'initialisation cyclique : son succès n'est pas une preuve complète de compatibilité.

- [ ] Ajouter les cas ST minimaux : création/lecture, écriture visible via un alias, incrément en boucle ; utiliser une entrée opaque et inspecter que les primitives subsistent avant de relever le comportement actuel.
- [ ] Faire produire un diagnostic module/opération pour une primitive non traduite, puis implémenter new, read et write en trois étapes séparées, en préservant le moment d'exécution.
- [ ] Pour une FFI réellement utilisée mais absente, lever une erreur nommant module et export ; conserver une politique explicite pour les modules non atteignables.
- [ ] Ajouter pure retournant une closure ou une chaîne PHP invocable, une action exécutée deux fois, l'ordre des effets et les exceptions.
- [ ] Définir puis tester le contrat numérique PHP/PBO : division entière, modulo avec signes et zéro, fractions Number, décalage logique et limites Int32. Comparer calcul constant et calcul avec entrée opaque.
- [ ] Rejouer RecursiveDictionaryInitialization après toute modification des closures, de la DCE ou de l'ordre des déclarations.
- [ ] Isoler le cas Ref.modify_ depuis une fonction locale opaque : la préparation de UnaryCallableCaptures a rencontré « Value of type int is not callable » avec le backend inchangé. La fixture validée emploie Ref.read/Ref.write ; ne pas présenter ce remplacement comme une correction de modify_.

Terminé quand : un scénario incorrect échoue explicitement ; chaque scénario supporté produit la valeur attendue et le nombre attendu d'effets. Les zones encore non supportées restent identifiées, sans être comptées comme gains de performance.

## R0 — P1 — Réutiliser les variables des branches exclusives

**Première optimisation livrée dans le checkout, sans commit.** RBTree : 693,896 ms avant, 287,723–296,565 ms après (environ 57–59 % de temps en moins). Somme des 14 tests : 816,808 ms avant, 407,670–417,580 ms après (environ 49–50 % de moins). Le README historique donne 269,606 ms pour RBTree et 381,61 ms au total : cette étape revient près de ces références, sans les dépasser. Journaux, état JIT et limites dans le [bilan R0](audit/2026-09-08/r0/report.md).

Profil initial, avant intégration : balance contenait 249 variables PHP distinctes pour 2 183 976 appels par action RBTree. Le profil natif passait 43,8 % de ses échantillons du thread principal dans zend_free_compiled_variables. La variante diagnostique ramenait la fonction à 33 variables, sans changer les branches ni les allocations : 282,6–283,5 ms, contre 644,6–677,4 ms pour la référence. Les mesures de l'implémentation intégrée figurent ci-dessus.

Origine : les branches du decision tree PBO dupliquent certains résultats ; CodeGen.purs, lignes 377–402, ajoute un suffixe frais à chaque Let. Les Branch, lignes 521–564, poursuivent le compteur à travers toutes les alternatives. Un renommage textuel suffit pour ce diagnostic RBTree ; il ne constitue pas une transformation générique sûre.

Première implémentation limitée : bindings anonymes en position terminale, hors contexte de boucle TCO, dont la valeur est un local, une lecture de champ ou un littéral scalaire. Conserver des noms uniques dans les arguments, conditions et initialisations, où plusieurs résultats peuvent rester vivants simultanément. Garder nextId inchangé pour l'unicité des labels et jonctions.

- [x] Isoler le coût par renommage des seuls binders de balance dans une copie du PHP, avec deux mesures et comparaison des arbres après chaque insertion.
- [x] Ajouter BranchBindingSlots et les tests directs du générateur ; rejouer DeepCase, ShadowedRename et les cas TCO pour les portées et valeurs après jonction.
- [x] Vérifier les closures échappantes, la capture récursive par référence et une boucle qui conserve une ancienne capture.
- [x] Utiliser un suffixe de slot dédié tout en conservant nextId pour les labels, jonctions et bindings uniques.
- [x] Réutiliser seulement les bindings anonymes terminaux à valeur simple, hors boucle TCO, sans réaffecter un binding déjà actif ; aucune règle spécifique à Test.RBTree.
- [x] Recompiler : bin/php/run -c passe ; 8 fixtures et 5 scénarios directs passent ; les 14 résultats sont conservés et balance passe de 249 à 33 variables.
- [x] Examiner séparément retours directs, partage des temporaires de résultats et decision tree après R0 : gain modeste (~15–25 ms) pour les temporaires, pas de gain stable pour le seul arbre compact. Voir R9 et le nouveau profil.
- [ ] Intégrer éventuellement le partage des résultats terminaux dans un changement distinct, avec tests des opérandes frères et jonctions ; ne pas le compter comme livré.

Terminé quand : le gain subsiste après régénération par PHPurs, les captures/portées restent correctes et aucun traitement spécial de Test.RBTree n'est ajouté au compilateur.

## R1 — P1 — Appeler un corps natif à arité exacte

Constat : Printer.purs, lignes 62–89, ajoute func_num_args, fallback de currying et gestion de surapplication à chaque fonction native. Les appels connus sont déjà directs, lignes 151–179, mais ciblent encore cette enveloppe.

- [ ] Ajouter une fixture couvrant appel saturé, application partielle, surapplication, fonction retournant une fonction et argument null.
- [ ] Émettre, pour une première fonction globale, un corps interne à arité exacte et conserver son enveloppe publique actuelle.
- [ ] Rediriger uniquement les appels dont la cible native et l'arité sont prouvées vers ce corps ; laisser les autres appels sur l'enveloppe.
- [ ] Émettre des retours directs et des types de retour précis dans le corps interne, sans union avec Closure due au currying.
- [ ] Étendre aux wrappers de GenNativeForeign.purs après validation du contrat FFI et des callbacks.

Mesure : Fib et Ackermann, puis petit microbenchmark d'appels ; vérifier aussi Polymorphism dans les deux profils M0. Compter les enveloppes contournées et les octets ajoutés avant de généraliser.

## R2 — P1 — Préserver les fonctions explicitement uncurried

Constat : CodeGen.purs, lignes 329–365, ramène Abs, UncurriedAbs et UncurriedEffectAbs au même PhpFunction. Printer.purs, lignes 95–131, recrée alors une IIFE et une closure avec protocole de currying pour plusieurs arguments.

- [ ] Porter la convention d'appel dans PhpAst pour les seules formes Uncurried* explicites.
- [ ] Émettre une closure PHP ordinaire à arité fixe pour ces formes ; conserver le chemin curried existant.
- [ ] Retirer uniquement les captures de $__fn devenues inutiles.
- [ ] Tester zéro/un/plusieurs arguments, captures, callbacks répétés, effets et exceptions.
- [ ] Étendre aux fonctions locales seulement si l'analyse de tous leurs usages prouve une arité fixe ; ne pas supposer cela pour les closures échappantes.

Mesure : callbacks de Church, ArrayOps et StateMonad ; ajouter un cas EffectFn. Critère : moins de closures intermédiaires sans modifier le comportement des appels partiels.

## R3 — P1 — Éliminer les enveloppes Effect avec une information explicite

Constat : CodeGen.purs, lignes 143–150 et 487–519, construit les actions et leurs binds ; executeIfOpaque, lignes 908–911, peut appeler phpurs_execute_effect sur une valeur pure. Le helper dans Printer.purs, lignes 389–395, invoque toute valeur is_callable.

- [ ] S'appuyer sur les régressions M1 des valeurs invocables, de la répétition et de l'ordre des effets.
- [ ] Ajouter au résultat de traduction une distinction valeur pure / action différée / résultat déjà exécuté.
- [ ] Supprimer d'abord le helper après un EffectPure prouvé.
- [ ] Remplacer ensuite le dispatch dynamique d'une action connue par une unique invocation directe.
- [ ] Éliminer les IIFE devenues vides et les captures inutilisées, sans exécuter une action à sa construction.

Mesure : benchmark dédié de chaînes Effect/bind/Ref et nombre de closures produites. StateMonad est une monade pure personnalisée : il ne suffit pas pour valider ce chantier.

## R4 — P1 — Réduire les copies de paramètres dans les boucles TCO

Constat : CodeGen.purs, lignes 270–273 et 306–309, copie arguments → temporaires → variables de boucle ; lignes 777–778, la boucle recopie vers les paramètres. Les transformations TCO locales et globales sont limitées à un groupe d'une fonction.

- [ ] Ajouter permutation de paramètres, dépendances croisées, arguments calculés et closure conservant une ancienne valeur de paramètre.
- [ ] Supprimer les affectations identiques, sans autre transformation.
- [ ] Pour une boucle sans capture problématique, affecter les paramètres depuis les temporaires en préservant une mise à jour simultanée.
- [ ] Remplacer le détour retour/variable/label quand une branche peut retourner directement.
- [ ] Dans un changement ultérieur distinct, transformer un cas pair/impair mutuellement terminal en un dispatcher unique ; ne jamais faire traverser un goto entre fonctions PHP.

Mesure : TCO puis ListOps/Records ; ajouter un vrai cas mutuellement récursif. Vérifier profondeur supportée, résultat et anciennes captures, pas seulement le temps.

## R5 — P2 — Réduire les clones de records avec des preuves locales

Constat : chaque Update clone son objet dans CodeGen.purs, lignes 566–580. Test.Records met à jour trois niveaux et produit trois clones par itération.

- [ ] Garder dans une fixture l'ancien record, un alias de son sous-record et une closure qui le capture ; vérifier leur immutabilité après update.
- [ ] Fusionner deux updates successifs lorsque l'intermédiaire n'a aucun autre usage.
- [ ] Replier l'update d'un record littéral fraîchement construit vers la construction finale, en préservant l'ordre d'évaluation.
- [ ] Compter les clones restants et mesurer Records avant d'ajouter une analyse plus large.
- [ ] Expérimenter la mutation seulement pour un objet local dont l'absence d'alias et d'échappement est prouvée dans PBO. Un unique usage du nom ne suffit pas.

Mesure : Records, allocations et mémoire ; régressions d'updates imbriqués et d'alias. Conserver le clone comme chemin de repli sûr.

## R6 — P2 — Réutiliser les constructeurs sans champ

Constat : CtorSaturated fait toujours new dans CodeGen.purs, lignes 582–599. CtorDef utilise déjà un singleton, lignes 601–610, mais sa clé ne contient que le nom du constructeur et peut entrer en collision entre modules.

- [ ] Ajouter deux modules qui déclarent chacun Nil ; vérifier classe et pattern matching.
- [ ] Qualifier la clé du singleton existant par module et constructeur.
- [ ] Utiliser ce même singleton pour les CtorSaturated sans argument.
- [ ] Vérifier les usages FFI avant de partager les objets, puis compter les allocations nullaires réellement évitées.

Mesure : ListOps/RBTree/AstTree et microbenchmark opaque de constructeurs nullaires. Ne pas extrapoler aux constructeurs à champs, qui peuvent dominer les allocations.

## R7 — P2 — Réduire le PHP émis et le coût de démarrage

Constat : Printer.purs, lignes 319–411, répète les helpers et treize classes génériques par namespace. Main.purs ne filtre la reachability qu'au moment des requires de main.mod.php ; le bundle a déjà accumulé tous les modules.

- [ ] Inventorier les références aux helpers/classes génériques, y compris dans les wrappers FFI.
- [ ] Omettre d'abord les classes génériques prouvées inutilisées, puis les helpers inutilisés.
- [ ] Faire correspondre les modules du bundle à ceux du mode modulaire pour un --main explicite ; vérifier mêmes résultats et ordre d'initialisation.
- [ ] Filtrer les modules hors fermeture des imports CoreFn avant optimisation, avec union des racines en mode plusieurs mains.
- [ ] Ajouter ensuite une DCE des bindings depuis main, en préservant SCC récursives, constructeurs, FFI et initialisations nécessaires.
- [ ] En dernier, partager les helpers restants dans un runtime unique avec noms qualifiés ; tester chargement direct d'un module et bundle.

Mesure : nombre de modules/bindings, octets PHP, mémoire et démarrage d'un petit programme avec OPcache désactivé puis activé. Séparer ces gains des boucles chaudes des 14 benchmarks.

## R8 — P1 — Compacter les captures avec un contrat FFI préservé

**Profil terminé, optimisation non intégrée.** LazyEvaluation construit puis force un million de closures capturantes. Remplacer les captures par des champs d'un objet invocable descend vers 50 ms, contre environ 90–102 ms pour les closures, sans modifier la chaîne ni le million d'appels. Le contrôle d'arité seul et le collecteur de cycles ne sont pas les coûts dominants. Voir le [rapport](audit/2026-09-08/lazy/report.md).

Le prototype générique (fonctions locales à un argument, captures par valeur) passe les 14 benchmarks mais échoue au contrat FFI : Foreign.typeOf/tagOf retourne object/Object au lieu de function/Function. Aff contient également un test instanceof Closure. Le prototype reste uniquement dans le dossier d'audit ; Printer.purs et le PHP courant ont été restaurés. Emballer chaque objet dans une vraie Closure réduit fortement le gain.

- [x] Profiler sous Xdebug puis JIT natif, comparer séparément contrôles d'appels, static, GC, objets invocables et wrappers Closure.
- [x] Vérifier profondeurs 0/1/2/17/1000, trois graines, appels répétés et le million d'appels de la charge originale.
- [x] Tester captures indépendantes, tableaux, objets mutables, références récursives, curry/surapplication, callbacks et exceptions ; conserver UnaryCallableCaptures et unary-callables.mjs.
- [x] Prouver la différence via la vraie FFI Foreign et retirer le prototype ; bin/php/run -c et les neuf fixtures passent après restauration.
- [x] Ajouter FunctionFFIBoundary : même fonction conservée à l'exécution, appels directs, record, Foreign, retours de Foreign, paramètre PHP Closure et callbacks conservés/répétés. PHP et JavaScript passent. La sonde prouve qu'une conversion du seul argument direct laisse le record incompatible ; voir le [bilan des frontières](audit/2026-09-08/r8-boundary/report.md).
- [x] Relever le TAST v3 réel : Func, champs de record et TypeApp des passages vers/depuis Foreign sont présents aux usages. L'annotation de déclaration foreign utilisant Handler vaut Any dans cette fixture, mais les points d'appel portent le record exact : ne pas se limiter à foreignAnnotations. Identifier aussi la boucle interne de buildThunks et son retour exporté ; les types ne prouvent pas seuls l'absence d'échappement ni le contrat PHP Closure.
- [ ] Prochaine petite étape : sur une copie isolée de LazyEvaluation, convertir seulement la racine retournée par buildThunks et garder les captures internes compactes. Vérifier profondeur zéro, appels répétés, captures et curry/surapplication ; compter les conversions (cible : au plus 1 000 sorties de chaînes, pas 1 000 000 enveloppes). Mesurer le gain avec R9 conservé.
- [ ] Définir ensuite une preuve générique des usages internes et de la frontière de retour pour le compilateur. Conserver les Closure actuelles lorsque les usages FFI, Foreign, records/collections ou exports ne sont pas couverts ; garder le contre-exemple des callbacks imbriqués.
- [ ] Réintroduire seulement ce cas démontré dans le backend, sans nom Test.*, puis vérifier les fixtures et les 14 benchmarks. Mesurer mémoire et temps ; refuser le changement s'il déplace le coût vers un wrapper par thunk.

Terminé quand : la reconnaissance des fonctions et leur exécution restent correctes à la frontière FFI, et un gain important subsiste après régénération complète. Les types TAST rendent les frontières analysables ; ils ne changent pas à eux seuls la représentation reconnue par le runtime PHP existant.

## R9 — P1 — Réduire les appels terminaux entre fonctions connues

**Première passe générique intégrée et mesurée.** Le [bilan R9](audit/2026-09-08/r9/report.md) compare le PHP entièrement régénéré avec la passe activée/désactivée, dans les deux ordres : **355,170–358,711 ms au total contre 399,670–409,605 ms** (11,1–12,4 % de temps en moins). RBTree : **239,411–240,267 ms contre 285,328–292,116 ms**. La référence historique du README est 381,61 ms au total et 269,606 ms pour RBTree.

La passe structure les jonctions en retours, compacte les copies simples et insère les corps feuilles connus aux appels terminaux saturés. Limites : 1 536 nœuds par corps et 3 072 nœuds ajoutés par caller, arguments inclus. Préserver les contrôles de types PHP émis, les captures et l'enveloppe publique ; laisser les autres appels inchangés. Aucun nom Test.* dans le critère. Sur les 299 modules de cette suite, seul RBTree change.

- [x] Reprofiler après R0 et comparer appels, allocations, temporaires, arité, singletons, TCO et GC ; voir le [profil précédent](audit/2026-09-08/rbtree-after-r0/report.md).
- [x] Valider le prototype sur une copie PHP puis vérifier les quatorze résultats dans les deux ordres.
- [x] Ajouter TerminalInlining et un test direct du PHP AST : branches, ordre/unicité d'évaluation, captures, noms frais, curry/surapplication, contrôles PHP, exceptions et budgets.
- [x] Implémenter l'inlining terminal borné de fonction native feuille du même module, sans récursion ni expansion itérative ; évaluer les arguments dans l'ordre et renommer les locaux. Les corps contenant des labels/sauts résiduels restent exclus.
- [x] Mesurer la vraie régénération après compaction : bin/php/run -c passe ; dix fixtures et trois scripts directs passent. Les sorties et les arbres après 1 285 insertions restent identiques.
- [x] Compter séparément : les 2 183 976 appels à balance disparaissent ; les 2 583 932 constructions de T et les comptes R/B/E/ins sont identiques. Le module RBTree grossit de 27 083 octets (+0,31 % sur l'ensemble des modules), pic mémoire inchangé à 42 MiB sur les contrôles complets.

Cette première étape R9 est terminée dans le checkout, sans commit de cette étape. L'extension aux cibles typées/non feuilles ou aux appels non terminaux reste hors du périmètre validé. Le TAST v3 conserve types, instanciations et layouts ; cette passe agit sur le flot de contrôle du PHP AST sans nouvelle représentation FFI. Réduire fortement les allocations d'arbres persistants demanderait une preuve distincte d'absence d'alias. La fixture de frontière FFI de R8 est validée ; le prochain essai mesure une conversion à la sortie des chaînes de LazyEvaluation.

## B1 — P1 build — Réutiliser une compilation PHP inchangée

Constat : Main.purs, lignes 96–98, renvoie toujours Nothing dans onSkipModule ; le cache importé n'est pas utilisé. Le helper PBO actuel fondé sur version et mtime CoreFn ne couvre pas à lui seul FFI, directives et dépendances.

- [ ] Relever les modules retraités dans les trois scénarios M0 : aucun changement, feuille modifiée, dépendance modifiée.
- [ ] Commencer par ne pas réécrire un index.php dont le contenu est identique.
- [ ] Définir une clé couvrant CoreFn, versions PHPurs/PBO, options/directives, FFI et empreintes des dépendances.
- [ ] Sauvegarder/restaurer imports, implementations, arités et contribution au bundle, en plus du PHP ; restaurer les refs Main nécessaires même sur cache hit.
- [ ] Activer le cache pour un scénario sans changement de graphe, puis étendre après régressions d'invalidation de chaque entrée.
- [ ] Comparer sorties et exécution avec un build sans cache ; traiter suppression de module et changement de main.

Mesure : temps de build, nombre de modules retraités et RSS. Aucun gain runtime attendu de ce cache seul.

## B2 — P2 build — Borner un cache mémoire PBO utile au frontend PHP

Constat : PBO Builder.purs, autour de la ligne 103, écrit les purmeta puis vide le cache RAM entre modules. Cache.js relit/désérialise les dépendances et clearPurmetaCacheImpl vide tout le cache. Ces choix peuvent aussi protéger la mémoire : ne pas simplement enlever tous les clear.

- [ ] Compter hits/misses, octets lus/écrits, durée de sérialisation et RSS sur une compilation PHP.
- [ ] Ajouter une politique opt-in au frontend PHP, bornée par taille ou nombre de modules, sans changer le défaut des autres backends.
- [ ] Comparer un petit budget et un budget moyen ; conserver la variante qui réduit les relectures avec une mémoire acceptable.
- [ ] Expliciter répertoire, format/version et invalidation avant toute réutilisation de .purmeta entre builds.

Mesure : compilation uniquement, PHP et résultats inchangés. Travailler dans purescript-backend-optimizer-phpurs et conserver un repli vers la politique actuelle.

## B3 — P2 build — Éviter les scans et sorties inutiles

Constat : Main.purs, ligne 109, cherche une FFI même pour un module sans foreign. Le fallback FfiSupport.js de PBO peut indexer récursivement le cwd. ComposerMerge.js, lignes 57–80, relit les CoreFn déjà chargés et impose output ; le mode bundle émet aussi tous les index.php.

- [ ] Court-circuiter la recherche FFI quand backendMod.foreign est vide.
- [ ] Fournir les racines de sources/packages déjà résolues ; borner le fallback au lieu de parcourir output, node_modules et les autres dossiers inutiles.
- [ ] Transmettre à Composer la liste des sources déjà chargées et respecter --output.
- [ ] Ajouter un mode explicite bundle seul, sans émission modulaire inutile.
- [ ] Pour les benchmarks, sortir Composer update du chemin d'exécution courant ; utiliser une préparation reproductible depuis le lock quand les dépendances ne changent pas.

Mesure : nombre de stat/read/write, temps FFI/Composer/printing et octets écrits ; tester source FFI locale, --ffi, dépendances et dossier de sortie non standard.

## B4 — P3 build — Profiler puis réduire les allocations du compilateur

Constat : Main.purs impose rewriteLimit = 10000 malgré une option CLI existante ; le compteur « Total AST Nodes » omet plusieurs formes. CodeGen accumule des tableaux par concat/snoc et recalcule freeVars ; flattenApp et flattenPhpCalls concatènent récursivement.

- [ ] Chronométrer lecture CoreFn, optimisation, traduction, impression et écriture ; rendre les logs détaillés optionnels.
- [ ] Corriger le comptage AST par un parcours complet et honorer --rewrite-limit avec le défaut actuel inchangé.
- [ ] Sur un module réellement coûteux, remplacer un seul accumulateur par une construction linéaire ; mesurer avant de généraliser.
- [ ] Réutiliser l'analyse des variables libres si disponible, ou la calculer une fois par nœud, en contrôlant le coût mémoire.
- [ ] Comparer quelques limites de réécriture sur les modules coûteux, avec temps build, taille PHP et runtime ; choisir un compromis mesuré.

Validation : grands records, longues applications et gros modules ; préserver IDs, captures, ordre d'évaluation et résultats. Les coûts supposés quadratiques doivent être confirmés par profilage avant refonte.

## Critère de clôture d'un point

- La régression ciblée échoue sur le comportement antérieur quand il s'agit d'un bug, et passe après la correction.
- Les résultats des benchmarks restent exacts ; aucun nouveau warning/placeholder runtime n'est masqué.
- La mesure avant/après utilise même entrée, même profil de directives, mêmes versions et mêmes paramètres JIT/OPcache.
- Le relevé distingue gain runtime, gain de démarrage, gain de compilation et coût mémoire/taille du code.
- Le point indique le commit et le relevé associés ; une hypothèse non confirmée est supprimée ou reformulée.
