# Mesures après R0

Minimum de dix mesures dans Bench.purs, après trois échauffements. Temps en millisecondes ; variantes mesurées séquentiellement puis dans l’ordre inverse.

## Contrôles isolés

| Variante | Série 1 | Série 2 |
| --- | ---: | ---: |
| baseline | 280.469 | 286.281 |
| branch-results | 260.351 | 265.852 |
| direct-results | 262.554 | 265.139 |
| compact-patterns | 278.520 | 282.644 |
| exact-arity | 276.223 | 275.997 |
| singletons | 273.204 | 260.967 |
| no-dead-tco | 268.417 | 276.351 |
| gc-off | 300.474 | 299.173 |

## Inlining diagnostique

| Variante | Série 1 | Série 2 |
| --- | ---: | ---: |
| baseline | 281.561 | 279.213 |
| inline-balance | 246.323 | 245.793 |
| branch-results | 258.201 | 260.676 |
