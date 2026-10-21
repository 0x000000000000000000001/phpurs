# Familles de tests codegen

Le 20 octobre 2026. Cartographie des **36 fichiers** de tests codegen avant
réorganisation physique.

## Répartition

- **Générateur direct / AST** : 24 fichiers.
- **CLI / cache / émission** : 10 fichiers.
- **FFI / runtime** : 2 fichiers.

Le détail et les conventions sont dans `tests/codegen/README.md`. Les familles
indiquent le point d’entrée principal ; plusieurs tests directs exercent aussi
une frontière FFI.

## Décision

La famille **CLI/cache** est maintenant déplacée dans `tests/codegen/cli/` ;
les imports relatifs et le script `test:codegen` ont été ajustés. La suite
complète passe (**102/102**).

Les trois familles sont maintenant dans `tests/codegen/direct/`, `cli/` et
`ffi/` ; les imports et le script `test:codegen` ont été ajustés. Suite
complète : **102/102**.
