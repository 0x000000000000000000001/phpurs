# Clôture du cleanup — maintenabilité

Le 20 octobre 2026. État consolidé du chantier, sans travail de performance.

## Résultat structurel

| Zone | Avant | Après |
| --- | ---: | ---: |
| `CodeGen.purs` | 946 | **764** |
| `ArrayRefs.purs` | 733 | **81** |
| `Printer.purs` | 494 | **28** (façade) |
| `Main.purs` | 242 | **146** |

Modules extraits : `CodeGen/Context`, `Tco`, `Effects`, `Bindings` ;
`ArrayRefs/Analysis`, `Rebuild`, `Support` ; `Printer/Expr`, `Decl` ;
`Main/Selection`, `Outputs`, `Options`, `Prepare` ; `RuntimeNames`.

## Tests

- **36 fichiers** en trois familles :
  - 24 générateur direct/AST ;
  - 10 CLI/cache/émission ;
  - 2 FFI/runtime.
- Helpers partagés : `temporaryRoot`, `runPhp`, `snapshotFiles`, `snapshotText`.
- Dernière exécution : **102/102**.
- Build : OK, sans warning.

## Décisions

- **Branches de `CodeGen`** : extraction non faite. La traduction des branches
  est intimement liée au noyau récursif `translateSyntax` ; une extraction
  imposerait des callbacks et des types de passage sans réduire réellement la
  complexité. Le noyau reste cohérent.
- **Autres `PhpRaw`** : les noms de helpers sont centralisés ; les chaînes
  restantes sont des builtins PHP, des opérateurs ou des extraits réellement
  opaques. Aucun constructeur supplémentaire n’apporte de clarté ici.
- **Frontières FFI** : documentées ; les erreurs actives sont couvertes ou
  exercées indirectement. Les modules purs n’ont pas de contrat d’erreur.
- **Fixtures** : les familles sont séparées et les helpers communs migrés. Les
  helpers locaux restants portent des scénarios spécifiques.

## Suite optionnelle

- Audit des types internes qui traversent une frontière publique.
- Messages d’échec de tests plus uniformes.
- Extraction des branches uniquement si un besoin concret de test ou de
  réutilisation apparaît.
