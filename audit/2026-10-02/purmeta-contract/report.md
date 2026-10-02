# B2 — Contrat de stockage et invalidation des `.purmeta`

## Contrat explicité

Le [contrat PHPurs](../../../docs/purmeta-storage.md) décrit le chemin, le format actuel et les conditions de lecture. Le contrat bas niveau est également présent dans `purescript-backend-optimizer-phpurs/docs/purmeta-cache.md`, avec un lien depuis le README PBO et des commentaires sur l'API PureScript.

- **Répertoire** : `<cwd>/.purmeta/<ModuleName>.purmeta`, avec les points conservés dans les noms, indépendamment de `--output` et du budget RAM. Création à la demande, répertoire de travail stable pendant le build, stockage détenu par ce build. Les fichiers résiduels restent sur disque sans donner de droit de lecture.
- **Format/version** : payload V8 brut d'une `BackendImplementations`, après marquage des constructeurs PureScript par `__ps`. Aucun protocole de fichier PBO versionné, identité de module/compilateur, clé d'entrée ni checksum. Le marqueur V8, le `schema: 1` du profil et le format B1 `PHPURS-MODULE-STATE 1` ont chacun une signification distincte.
- **Invalidation** : ensemble d'appartenance vide au démarrage du processus et après chaque `beginPurmetaBuild`. Seule une écriture réussie dans la portée courante rend un module lisible. Clear/éviction RAM conservent cette appartenance ; un nouveau build la réinitialise.
- **Restauration B1** : l'état complet validé est restauré par PHPurs, puis ses implémentations sont réécrites et publiées par PBO dans la portée courante. La validité inter-builds provient des clés et du protocole B1.
- **Erreurs et limites actuelles** : écritures directes synchrones dont les erreurs interrompent le build, lectures manquantes/échouées donnant `Nothing`, absence de validation complète du payload et de garantie de partage des constructeurs reconstruits. Ces limites sont intégrées au contrat de stockage temporaire.

Le contrat spécifie aussi les informations et garanties à apporter avant toute réutilisation inter-builds : namespace/version sémantique, identité et invalidation des entrées/dépendances/contexte précédent, décodage typé validé, intégrité et publication atomique, isolation des producteurs, restauration du contexte de directives et de noms privés/spécialisés. Le backend natif Go, qui conserve une table mémoire sans fichiers V8, est décrit séparément dans le contrat PBO.

## Lacune reproduite et correction

Avant correction, `currentBuildModules` était initialisé à `null`. Le contrôle d'appartenance n'était actif qu'après `beginPurmetaBuild` : un appel direct à `readPurmetaSync` dans un nouveau processus pouvait donc charger des fichiers résiduels avant la première invocation du builder.

La nouvelle régression de `test/purmeta-build-cache.mjs` crée des fichiers valides et corrompus, puis lance un processus Node neuf. Elle vérifie leur rejet avant tout appel à `beginPurmetaBuild`, y compris après un clear RAM. Des interceptions de `fs.existsSync` et `fs.readFileSync` exigent zéro sonde ; les compteurs PBO doivent relever trois rejets et zéro miss RAM. Le même processus publie ensuite une implémentation, la relit après clear, puis constate son invalidation au prochain begin.

Ce test a d'abord **échoué sur le code précédent**, avec `old metadata must be blocked before builder initialization`. La correction initialise désormais l'appartenance à un `Set` vide et supprime les deux cas spéciaux `null`. Une publication directe reste possible dans cette portée initiale ; les builders continuent à ouvrir une portée neuve à chaque exécution.

## Validation

- `npm run build` réussit avec **zéro avertissement et zéro erreur**.
- `npm run test:codegen` passe **61 contrôles PHPurs**, incluant PHP modulaire/bundle exécuté, budgets 0/1/16/64/128, builds frais/restaurés/mixtes et sorties personnalisées.
- **24 contrôles PBO** passent : quatre de cycle de build, quatre de budget, trois de profilage, six LRU, quatre de lookup et trois de visibilité parallèle.
- La régression initialement rouge passe après correction ; les tests confirment aussi la publication après codegen dans le builder séquentiel, la republication d'un hit validé, les resets sur builds vides/répétés et la visibilité du builder parallèle.

Commandes depuis le dépôt PHPurs :

```sh
npm run build
npm run test:codegen
node ../../purescript-backend-optimizer-phpurs/test/purmeta-build-cache.mjs output
node ../../purescript-backend-optimizer-phpurs/test/purmeta-budget.mjs output
node ../../purescript-backend-optimizer-phpurs/test/purmeta-stats.mjs output
node ../../purescript-backend-optimizer-phpurs/test/purmeta-lru.mjs output
node ../../purescript-backend-optimizer-phpurs/test/implementation-lookup.mjs output
node ../../purescript-backend-optimizer-phpurs/test/parallel-visibility.mjs output
```

Le bundle reconstruit et testé porte la SHA-256 `6f4d128fbc2cb59b9ad820c6bf1c4fa4d5ffb84191b076ef9ddb09b1f61a9d51`. Les contrôles `git diff --check` et les vérifications des nouveaux fichiers passent dans les deux dépôts.

La dernière case B2 est close. Le choix de budget est celui du [lot comparatif](../purmeta-budget-comparison/report.md) ; ce lot-ci valide le contrat de stockage et la fermeture du chemin de lecture antérieur à l'initialisation du builder.
