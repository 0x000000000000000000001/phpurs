# LazyEvaluation : coût des captures et limite FFI

Le profil identifie l'allocation et la destruction des closures capturantes comme première piste. Une représentation par objet invocable divise presque par deux le temps du test, mais change le comportement de la FFI `Foreign`. **Le prototype a été retiré du générateur.** Aucun gain de ce prototype n'est présenté comme intégré.

## Charge et mesure

Le test PureScript reste inchangé : 1 000 chaînes de profondeur 1 000, construites puis forcées, résultat `1000000`. Le newtype Lazy est déjà effacé ; aucun wrapper d'ADT n'est créé par thunk. La boucle de construction est déjà transformée en TCO.

Le runner appelle l'action générée et conserve le protocole de `Bench.purs` : trois échauffements, dix mesures, minimum. Les temps excluent le build et Composer. PHP 8.5.4, OPcache CLI, JIT 1255 avec buffer 128 Mio, Xdebug absent des mesures de performance. Les copies diagnostiques utilisent `opcache.file_update_protection=0` pour éviter un faux écart sur les fichiers fraîchement écrits. Deux séries de variantes sont exécutées dans des ordres inverses. La machine est partagée ; les fourchettes observées ne sont pas des intervalles statistiques.

La première mesure isolée donne **91,870 ms**, proche des **~91,955 ms** du [README historique d'altbak.pub](../../../../../altbak.pub-phpurs/README.md). Les séries suivantes fluctuent selon l'état de la machine ; les chiffres complets sont dans [ablation-results.json](ablation-results.json), les journaux voisins et le tableau généré [measurements.md](measurements.md).

## Profil

Xdebug, sans JIT, compte **1 000 000 appels** à la closure capturant le thunk précédent, **1 000 appels** à buildThunks et un appel à runManyTimes. La closure représente 81,2 % du temps propre instrumenté et buildThunks 12,3 %. Ces proportions ne décrivent pas l'exécution avec JIT : l'instrumentation de chaque appel pèse lourd. Les deltas mémoire cumulés du profiler ne sont pas des comptes d'allocations. Voir [callgrind-summary.json](callgrind-summary.json).

L'échantillonnage natif de cinq secondes avec JIT fournit **4 196 échantillons du thread principal**. Environ 43 % de ses feuilles se trouvent dans zend_objects_store_del, zend_array_destroy (dont sa branche cold), zend_closure_free_storage et zend_destroy_static_vars. La création des closures, la duplication de leurs tables de captures et les instructions de liaison apparaissent aussi. Les frames JIT inconnues restent non attribuées. Le collecteur de cycles ne se déclenche pas pendant les 83 actions de ce relevé : **0 passage, 0 cycle**, ce qui ne supprime pas le coût de la destruction normale par comptage de références. Voir [native-summary.json](native-summary.json) et [native-run.log](native-run.log).

## Expériences

- Supprimer les contrôles d'arité/surapplication des deux closures : pas de gain stable. Ajouter un retour int ou static ne change pas ce constat.
- Désactiver le GC : pas de gain stable, cohérent avec l'absence de collection observée.
- Stocker la capture dans un objet avec __invoke : environ 46–51 ms dans les premières expériences, tout en conservant la chaîne et ses appels. Le protocole complet de surapplication conserve l'essentiel du gain.
- Reconvertir chaque objet en Closure, par fromCallable, référence de méthode ou closure liée à l'objet : l'essentiel du gain disparaît. Les premiers relevés donnent environ 83–89 ms contre 92–96 ms pour la référence. Cette voie n'est pas retenue pour le chantier des gros gains.

Toutes les variantes calculent les résultats attendus aux profondeurs 0, 1, 2, 17 et 1 000, avec graines −3, 0 et 11, y compris en forçant deux fois la même chaîne. [validation.json](validation.json) en conserve les résultats. Ces vérifications numériques seules ne prouvent pas la compatibilité FFI.

## Prototype et contre-exemple

Le prototype [object-prototype.patch](object-prototype.patch) remplaçait génériquement les closures locales à un argument et captures exclusivement par valeur par des classes anonymes invocables. Il rechargeait les captures dans des variables locales à chaque invocation, conservait le protocole d'appel existant et laissait les captures récursives par référence sur le chemin Closure. Aucun traitement de Test.LazyEvaluation n'était ajouté au backend.

Après reconstruction complète, les 14 résultats étaient corrects : LazyEvaluation **98,215 → 49,519 ms**, somme des minima **397,439 → 361,751 ms**. RBTree variait de 280,853 à 294,557 ms. Le README historique donne 91,955 ms pour LazyEvaluation et 381,610 ms pour la somme. Ce résultat expérimental ne peut pas être livré comme un progrès compatible.

La vraie FFI `phpurs-foreign/src/Foreign.php` distingue Closure des autres objets. Sur le même thunk donnant 10, typeOf/tagOf donne **function/Function** avant, **object/Object** avec le prototype. Le test est exécuté, pas déduit : [ffi-validation.json](ffi-validation.json). Un paramètre PHP typé Closure refuserait aussi l'objet. `Effect/Aff.php` emploie instanceof Closure dans son trampoline, et `Simple/JSON.php` distingue également ces représentations. Ces paquets partagés n'ont pas été modifiés.

Le test direct [unary-callables.mjs](../../../tests/codegen/unary-callables.mjs) passe avec le générateur initial et refuse le prototype sur ce contrat ; voir [prototype-ffi-failure.log](prototype-ffi-failure.log). Le TAST v3 est toujours disponible, mais le type PureScript Func ne prouve pas à lui seul la représentation exigée à la frontière PHP/FFI.

## État final et suite

Printer.purs est restauré. Le PHP régénéré de LazyEvaluation est identique, par SHA-256, au snapshot de départ ; ses métadonnées TAST restent présentes : [restoration.json](restoration.json). `bin/php/run -c` réussit avec les 14 sorties correctes, LazyEvaluation 101,390 ms et somme 406,653 ms. Les trois runs complets sont dans [full-results.json](full-results.json). R0 reste en place.

Neuf fixtures passent, dont la nouvelle UnaryCallableCaptures : captures conservées dans une boucle, appels répétés, FFI typée Closure, surapplication et effets différés. Les scénarios directs du printer couvrent aussi captures scalaires/tableaux/objets, récursion par référence, application partielle, callbacks PHP et exceptions ; les cinq scénarios R0 passent. Le runner standard exige toujours le paquet absent phpurs-math ; [run-fixtures.py](run-fixtures.py) utilise le même projet isolé à dépendances ciblées que R0. Cette validation n'est pas la suite complète officielle.

Une tentative de fixture utilisant Ref.modify_ a également rencontré « Value of type int is not callable » sur le backend inchangé. La fixture finale utilise Ref.read/Ref.write pour tester les effets ; l'incident est noté en M1, sans prétendre l'avoir corrigé.

Prochaine étape R8 : prouver une frontière interne/FFI compatible avec la représentation compacte, en s'appuyant sur les types et instanciations TAST. Commencer par une fonction transmise directement puis dans un record et via Foreign ; conserver le chemin actuel pour les usages inconnus. Convertir à une frontière prouvée pourrait garder le gain interne, alors qu'une conversion à chaque thunk le consomme. RBTree reste ~70 % du temps et LazyEvaluation ~25 % ; le reste représente moins de 5 %.

Reproduction : `python3 ablate.py` depuis ce dossier pour les copies diagnostiques, résultats et contrôles FFI. `python3 native-sample.py` échantillonne uniquement son enfant PHP ; l'autorisation système d'inspection de ce processus est nécessaire. Les gros profils et les projets temporaires restent dans raw/, ignoré par Git.
