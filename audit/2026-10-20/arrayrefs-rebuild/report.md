# Découpage d’ArrayRefs

Le 20 octobre 2026. La réécriture des reconstructions de nœuds quitte
`ArrayRefs.purs`.

## Résultat

- `ArrayRefs.purs` : **733 → 646 lignes**.
- Nouveau `ArrayRefs/Rebuild.purs` : **96 lignes**
  (`mutateRebuilds`, collecte des affectations locales).
- Nouveau `ArrayRefs/Support.purs` : **21 lignes**
  (`exprChildren`, `fieldAccessor`), partagé avec l’analyse.
- `ArrayRefs.purs` conserve l’analyse d’ownership (`fixpoint`, scans,
  passabilité) et la réécriture des paramètres (`rewriteBody`).

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**.

Prochaine cible : extraire l’analyse d’ownership restante dans un module dédié,
ou commencer le découpage du printer.
