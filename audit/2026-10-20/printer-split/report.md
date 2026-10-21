# Découpage du printer

Le 20 octobre 2026. `Printer.purs` est séparé en façade, expressions et
déclarations.

## Résultat

- `Printer.purs` : **494 → 28 lignes** de façade publique.
- `Printer/Expr.purs` : **346 lignes** (expressions, curry, noms PHP).
- `Printer/Decl.purs` : **172 lignes** (déclarations, corps exacts, assemblage
  de module).
- `Printer.js` est déplacé vers `Printer/Expr.js`.
- L’API publique `genCurry`, `printExpr`, `printPhpFile`, `safeFuncName` et
  `safeName` reste inchangée.

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**.

Le module d’expressions reste le plus gros morceau du printer et pourra être
sous-découpé plus tard si nécessaire.
