# Comparaison finale des variantes

Deux séries séquentielles, ordre inversé. Minimum de dix mesures dans Bench.purs, après trois échauffements. Millisecondes.

| Variante | Série 1 | Série 2 |
| --- | ---: | ---: |
| baseline | 102.311 | 95.279 |
| unary-direct | 104.336 | 95.234 |
| unary-typed | 102.886 | 96.369 |
| static | 98.283 | 95.956 |
| object-thunk | 44.696 | 43.751 |
| object-full | 50.856 | 47.685 |
| object-closure | 92.787 | 87.063 |
| method-closure | 87.936 | 81.752 |
| bound-closure | 85.271 | 82.712 |
| gc-off | 97.929 | 97.752 |

Les variantes object-thunk et object-full changent le contrat Foreign et ne sont pas intégrées. Les variantes avec Closure préservent ce contrat mais réduisent fortement le gain. Machine partagée : les écarts entre séries peuvent inclure la charge extérieure.
