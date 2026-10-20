# RBTree — profil après R4/R7

Le 20 octobre 2026. Profil de la sortie active (R4, runtime partagé), sans
modification du générateur. Xdebug n’est pas installé dans PHP 8.5.4 ; le profil
principal est un échantillonnage natif `/usr/bin/sample` sur l’action RBTree
seule.

## Mesures

- RBTree ABBA : **54,61 ms** ; campagne pure : **55,20 ms**.
- 100 000 insertions, **2 283 976** appels à `ins` privé, **499 934** constructions
  de nœuds `T` privés, 0 appel `balance` (inliné), pic mémoire 16 MiB.
- Les classes publiques `R`/`B` sont construites une seule fois à l’initialisation
  du module.

## Échantillonnage natif

Les symboles visibles les plus fréquents sont :

| Symbole | Échantillons |
| --- | ---: |
| `zend_object_dtor_property` | 168 |
| `ZEND_NEW_SPEC_CONST_UNUSED_HANDLER` | 129 |
| `zend_objects_store_del` | 106 |
| `object_init_ex` | 104 |
| `_emalloc` / `_efree` | 138 |
| `zend_object_std_dtor` | 55 |
| `gc_possible_root` | 40 |

Le JIT rend les adresses PHP opaques, mais la signature est claire :
allocation, initialisation et destruction d’objets dominent les symboles
observables. Le temps restant n’est plus dans les copies TCO.

## GC

Cinq processus alternés, GC normal contre `gc_disable()` :

| Variante | Médiane RBTree |
| --- | ---: |
| normal | 56 039 µs |
| GC désactivé | 56 073 µs (+0,06 %) |

Le GC n’est pas un levier sur cette charge. Le coût est la vie des objets
eux-mêmes, pas leur collecte cyclique.

## Conclusion

R4 est clôturé pour l’optimisation : les copies TCO ne sont plus le premier coût
RBTree. La prochaine piste mesurable est la **représentation/mutation des nœuds
privés `T`** : prouver qu’une insertion peut réutiliser les nœuds non observés
au lieu d’en allouer 499 934, ou tester une représentation compacte des champs
privés. Toute variante doit conserver les anciennes racines, les frontières FFI
et les 14 résultats.

Artefacts : `native-sample.txt`, `gc-off.json` et `results.json` dans ce dossier.
