# Templates du runtime paramétrés

Le 20 octobre 2026. Les définitions du runtime utilisent les noms canoniques
de `Phpurs.RuntimeNames`.

## Résultat

- `Printer/Runtime.purs` : **129 lignes**.
- Les templates utilisent des placeholders (`__CURRY__`, `__EFFECT__`,
  `__REF_NEW__`, `__REF_READ__`, `__REF_WRITE__`) remplacés une seule fois par
  les constantes de `RuntimeNames`.
- Aucun littéral `phpurs_curry_fallback`, `phpurs_execute_effect` ou
  `phpurs_ref_*` ne reste dans le module.
- Les appels générés et le contenu du runtime restent identiques.

## Vérification

- `npm run build` : OK, sans warning.
- `npm run test:codegen` : **101/101**.
- Build `--no-cache --main App` comparé à la sortie active :
  **110 fichiers PHP identiques**.

Prochaine étape Phase 3 : réduire les `PhpRaw` récurrents qui ne sont pas des
noms de helpers.
