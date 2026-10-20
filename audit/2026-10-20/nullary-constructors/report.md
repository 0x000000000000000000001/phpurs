# R6 — Clés de singletons nullaires

Le 20 octobre 2026. La collision de clé entre modules est **corrigée et
testée**. La réutilisation du singleton par `CtorSaturated` a été mesurée mais
n'est **pas retenue** : le gain est limité à Primes, ListOps est neutre, et la
variante sûre ajoute 59 081 octets.

## Collision corrigée

`CtorDef` utilisait `__phpurs_data0_Nil` sans qualification. ListOps et Primes
déclarent tous deux `Nil` : le second module pouvait réutiliser l'objet du
premier. La clé est maintenant :

```text
__phpurs_data0_Test_ListOps_Nil
__phpurs_data0_Test_Primes_Nil
```

`CtorSaturated` conserve `new`. La classe et le singleton global de chaque
module sont vérifiés par
[`nullary-constructors.mjs`](../../../tests/codegen/nullary-constructors.mjs) :
les deux singletons ont la bonne classe et ne sont pas le même objet.

Effet sur la sortie : **18 modules** changent, pour **+922 octets** sur les 307
fichiers. Aucun gain runtime n'est revendiqué pour cette correction.

## Réutilisation du singleton mesurée

Deux variantes ont été essayées à partir de la sortie R5 :

| Variante | Primes | ListOps | PHP |
| --- | ---: | ---: | ---: |
| `CtorSaturated` actuel (`new`) | 746 µs | 67,1 µs | 7 430 414 o |
| `??=` sur clé interne qualifiée | 723 µs | 66,9 µs | +59 081 o |
| lecture directe du global public | 718 µs | 68,1 µs | −40 Ko |

La variante `??=` est sûre mais ajoute une vérification et du texte à chaque
site de construction. La lecture directe est plus petite et rapide, mais elle
suppose que l'affectation `CtorDef` du module a déjà eu lieu avant tout usage,
y compris dans les initialiseurs de haut niveau et les modules FFI. Cette preuve
d'ordre n'est pas encore établie génériquement.

## Décision

Seule la clé qualifiée est intégrée. `CtorSaturated` garde `new` comme chemin
sûr. R6 reste ouvert pour une éventuelle réutilisation du singleton après une
preuve d'initialisation par module ; la collision, elle, est fermée.

Validation finale : build sans warning, **98/98** contrôles codegen, 14 valeurs
identiques après régénération. Les mesures des variantes sont dans
[`results.json`](results.json).

Prochaine micro-étape recommandée : **R7**, inventorier les helpers/classes
génériques réellement émis et le coût de démarrage avant toute DCE.
