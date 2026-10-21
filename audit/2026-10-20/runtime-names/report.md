# Noms canoniques du runtime PHP

Le 20 octobre 2026. Les noms des helpers runtime sont regroupés dans
`Phpurs.RuntimeNames`.

## Résultat

- Nouveau module `RuntimeNames` : `curryFallback`, `executeEffect`, `refNew`,
  `refRead`, `refWrite` et le qualificateur `qualified`.
- Les sites d’appel de `CodeGen`, `CodeGen/Effects`, `Printer/Expr`,
  `Printer/Decl` et `GenNativeForeign` utilisent ces constantes.
- Les chaînes de préambule de `Printer/Runtime.purs` restent la source des
  définitions ; un contrôle vérifie que chaque nom canonique y est bien défini.
- Aucune sortie PHP ne change.

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**.

Prochaine étape possible : rendre les templates de `Printer/Runtime.purs`
eux-mêmes paramétrés par ces constantes, ou poursuivre la réduction des
`PhpRaw` récurrents.
