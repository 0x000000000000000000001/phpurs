# Extraction de ArrayRefs.Analysis

Le 20 octobre 2026. L’analyse d’ownership quitte `ArrayRefs.purs`.

## Résultat

- `ArrayRefs.purs` : **646 → 81 lignes**.
- Nouveau `ArrayRefs/Analysis.purs` : **590 lignes**.
- `ArrayRefs.purs` ne garde que `optimize`, `rewriteBody` et `ownedParam`.
- L’analyse contient les scans de passabilité, les carriers TCO, les sites,
  `fixpoint` et la détection de constructions partagées.
- Les helpers `Rebuild` et `Support` restent séparés.

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**.

Cette séparation rend la frontière analyse/réécriture explicite ; le module
d’analyse reste gros et pourra être sous-découpé indépendamment.
