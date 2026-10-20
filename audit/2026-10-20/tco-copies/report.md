# R4 — Supprimer les copies de paramètres TCO

Le 20 octobre 2026. Intégré dans `CodeGen` et mesuré sur la sortie R7. Les
boucles TCO bufferisées passent leurs paramètres par un temporaire
`$__tco_var_*`, puis recopient le temporaire vers le paramètre au label. La
version intégrée supprime ce protocole pour 41 boucles sûres et conserve les
5 boucles restantes (constructeurs privés ou boucles imbriquées).

## Inventaire

- 46 boucles TCO dans 20 modules.
- 85 copies d’initialisation et 85 recopies au label.
- 58 affectations de saut, toutes alimentées par `$__tco_*` ou un littéral.
- 41 boucles directes, 5 bufferisées ; le prototype textuel avait supprimé 174 lignes et 285 références, l’intégration garde 30 références `__tco_var_*` dans les replis.
- PHP : 1 493 656 → **1 483 431 octets** (−0,68 %).
- 30 références `__tco_var_*` restent dans les boucles bufferisées (modules à
  constructeurs privés et boucles imbriquées).

## Mesure ABBA, 11 processus

| Test | Contrôle | Intégré | Écart |
| --- | ---: | ---: | ---: |
| Tail Call Optimization | 74,73 µs | **50,39 µs** | **−32,57 %** |
| Lazy Evaluation | 812,71 µs | **512,85 µs** | **−36,90 %** |
| Polymorphism | 6 457,71 µs | **4 933,75 µs** | **−23,60 %** |
| Ackermann | 44,64 µs | **36,34 µs** | **−18,60 %** |
| Primes | 731,41 µs | **719,90 µs** | −1,57 % |
| List Processing | 66,44 µs | **63,65 µs** | −4,21 % |
| Records | 1 039,03 µs | **1 026,16 µs** | −1,24 % |
| RBTree | 54 852,04 µs | **54 607,83 µs** | −0,45 % |
| **Total** | **66,197 ms** | **64,074 ms** | **−3,21 %** |

State passe de 2,10 à 2,67 µs (+0,57 µs absolus), cellule négligeable.
Les 14 valeurs passent.

## Intégration et garde RBTree

La première tentative activait les registres directs dans toutes les boucles.
RBTree est alors passé d’environ 55 à 105 ms : `ArrayRefs` ne reconnaissait plus
les porteurs `__tco_var_*` et désactivait la mutation en place de `ins`. La
version intégrée désactive donc les registres directs dans les modules à
constructeurs privés (`regions.privateConstructors` non vide). RBTree retrouve
55 ms, tout en conservant les gains TCO/Lazy/Poly/Ackermann.

`LoopContext.direct` est vrai seulement si :

- le module n’a pas de constructeurs privés (garde ArrayRefs) ;
- aucune boucle TCO imbriquée n’est présente dans le corps ;
- les arguments de saut sont déjà évalués dans `$__tco_*`, donc l’affectation
  des paramètres est simultanée et sûre.

Les captures de boucles imbriquées restent bufferisées ; aucun nom public ne
change.

## Campagne finale

Campagne à trois processus : **64,707 ms**, 14 sorties validées.
`runp -c` : **64,32 ms**. Les 14 valeurs et **100/100** contrôles codegen
passent. Le PHP actif fait **110 fichiers / 1 483 431 octets**.

Artefacts : sortie active `run/bak/php/modes/pure/output`, campagne
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-r4-campaign-20261020`, contrôle et prototype dans `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/`.
