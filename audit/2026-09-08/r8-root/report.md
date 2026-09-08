# R8 — Conversion de la racine à la sortie de buildThunks

**L'expérience conserve le gain des captures compactes et le contrat public testé. Elle n'est pas encore intégrée au compilateur.** Sur la copie PHP avec conversion de chaque racine, LazyEvaluation prend **46,028–46,349 ms en isolation**, contre **87,350–88,710 ms** pour le témoin. Les quatorze résultats passent ; le total mesuré est **321,164–324,011 ms**, contre **352,631–363,984 ms** avec R9 seul, soit environ **8,1–11,8 % de temps en moins** dans les deux ordres.

Le backend et les fichiers PHP actifs sont inchangés. Le snapshot de RBTree correspond exactement à l'empreinte de la version R9 mesurée. Les mesures ci-dessous portent sur des copies diagnostiques ; le total actif n'est donc pas annoncé comme amélioré.

## Modification isolée

`prepare.py` fige le PHP actuellement généré, puis produit trois variantes. Seul `Test.LazyEvaluation/index.php` change ; `App` est copié pour charger la bonne variante, les autres modules pointent vers le même snapshot.

- **baseline** : PHP actuel, avec R9 conservé.
- **root-convert** : remplacer la closure capturant le thunk précédent par un objet invocable à champ privé. Conserver intégralement le corps et le protocole de surapplication. Convertir la racine avec `Closure::fromCallable` à la sortie de `buildThunks`, seulement si elle est un objet interne créé par cette transformation.
- **escaping-root** : même représentation, mais effectuer la surapplication avant la conversion. Convertir seulement une racine qui est effectivement retournée à l'appelant.

Le marqueur `R8InternalThunk` appartient exclusivement à la copie diagnostique. Il évite de reconvertir une Closure ou un autre callable reçu de l'extérieur, notamment à profondeur zéro. La structure de la chaîne, les branches, la boucle TCO, les valeurs et les instanciations du programme restent inchangées. Les classes et noms du benchmark sont utilisés uniquement pour cibler cette expérience, sans nouvelle règle de production `Test.*`.

Le PHP existant de `runManyTimes` appelle `buildThunks(1000, base, unit)` : le troisième argument force déjà la racine dans le protocole de surapplication. La seconde variante peut donc éviter la conversion dans cette charge, tout en retournant une Closure pour l'appel public à deux arguments.

## Comptage séparé des mesures

Les compteurs s'exécutent sans JIT dans d'autres copies ; ils ne sont pas présents pendant les chronos. Chaque mode construit et force un million de nœuds. Le mode `public-roots` demande d'abord chaque fonction avec deux arguments, puis l'invoque.

| Variante et mode | Appels buildThunks | Nœuds créés | Invocations des nœuds | Conversions en Closure |
| --- | ---: | ---: | ---: | ---: |
| baseline / action actuelle | 1 000 | 1 000 000 closures | 1 000 000 | 0 |
| root-convert / action actuelle | 1 000 | 1 000 000 objets | 1 000 000 | 1 000 |
| escaping-root / action actuelle | 1 000 | 1 000 000 objets | 1 000 000 | 0 |
| root-convert / racines publiques | 1 000 | 1 000 000 objets | 1 000 000 | 1 000 |
| escaping-root / racines publiques | 1 000 | 1 000 000 objets | 1 000 000 | 1 000 |

Le résultat vaut toujours `1000000`. Voir [counts.json](counts.json).

## Contrat validé

`validate.php` compare les observations exactes des trois variantes, sur les profondeurs **0, 1, 2, 17 et 1 000**, avec trois graines **−3, 0 et 11** :

- racines reconnues comme Closure, tags `function/Function` dans la vraie FFI Foreign et dans un record ; paramètre PHP Closure accepté ;
- appels répétés, application partielle de buildThunks, surapplication du constructeur directement et via sa fonction partielle ;
- fonction d'entrée conservée par identité à profondeur zéro, y compris un objet invocable externe qui doit rester inchangé ;
- nouvelle chaîne construite à partir d'une racine déjà retournée : les deux chaînes restent utilisables ;
- capture mutable commune à deux chaînes, aucune invocation pendant la construction, ordre et répétition corrects ;
- même objet exception propagé lors de deux invocations ; surapplication d'un résultat Int toujours en erreur.

Les trois observations sont identiques. Voir [validation.json](validation.json). Cela couvre le contrat testé ; cela ne prouve pas tous les usages PHP d'une Closure, l'introspection, ni la sûreté d'une transformation générale des fonctions qui s'échappent via des agrégats arbitraires.

## Mesures

Même protocole `Bench.purs` : trois échauffements, minimum de dix passages ; le total est la somme des quatorze minima. PHP **8.5.4**, OPcache CLI, JIT **1255**, buffer **128 MiB**, Xdebug absent. Pas de cache de fichiers OPcache, ni de compteurs/profiler. Exécutions successives, puis ordre inversé. Les journaux et l'état JIT réel sont conservés dans [measurements.json](measurements.json).

| Ordre | Variante | Lazy isolé | Lazy dans les 14 | RBTree dans les 14 | Total des 14 |
| --- | --- | ---: | ---: | ---: | ---: |
| 1 | baseline | 87,350 ms | 95,842 ms | 238,624 ms | 352,631 ms |
| 1 | root-convert | 46,028 ms | 49,003 ms | 256,499 ms | 324,011 ms |
| 1 | escaping-root | 46,070 ms | 50,187 ms | 257,405 ms | 326,388 ms |
| 2 | escaping-root | 46,115 ms | 49,127 ms | 254,977 ms | 323,164 ms |
| 2 | root-convert | 46,349 ms | 49,048 ms | 253,530 ms | 321,164 ms |
| 2 | baseline | 88,710 ms | 99,719 ms | 245,989 ms | 363,984 ms |

Les **1 000 conversions de racines conservent l'essentiel du gain**. Éviter ces conversions ne montre pas d'avantage supplémentaire dans ces mesures ; ce n'est pas un préalable à l'intégration.

RBTree, pourtant identique par SHA-256, prend davantage de temps dans les processus des variantes. La cause n'est pas isolée par cette expérience : charge de la machine et effets du chargement/JIT restent des possibilités. On ne soustrait donc pas artificiellement son écart pour annoncer 310 ms. Le résultat complet observé des variantes est bien **321–326 ms**.

Le [README historique d'altbak.pub](../../../../../altbak.pub-phpurs/README.md) donne **91,955 ms pour LazyEvaluation et 381,61 ms au total**. La copie avec conversion de racine est environ **15–16 % sous ce total historique** ; la comparaison contrôlée avec le témoin actuel est celle utilisée pour attribuer le gain à l'expérience.

Le pic PHP alloué reste à **4 MiB** en isolation et **42 MiB** sur la suite complète. Le module passe de **13 817 à 14 097 octets** avec conversion de racine (+280 octets). Ces métriques ne montrent pas de gain mémoire à leur granularité actuelle.

## Suite dans le compilateur

La prochaine étape est une intégration générique bornée, en commençant par la conversion de racine simple. Il faut prouver, sur la syntaxe typée, que les fonctions créées dans la boucle et leurs captures par valeur restent internes jusqu'au retour fonctionnel : appels, captures et passage au tour suivant sont à distinguer des passages FFI, coercitions vers Foreign, agrégats et usages inconnus. Les fonctions non couvertes conservent leur représentation actuelle.

Le TAST v3 apporte les `Func`, layouts et instanciations aux points d'appel. Il faut utiliser ces informations et les usages avant que CodeGen ne traduise les enveloppes `Typed`/`TypeApp`, puis porter la décision explicitement à l'AST PHP. Le caractère exporté de `buildThunks` impose de conserver une racine publique Closure. Un type fonctionnel seul n'établit pas l'absence d'échappement intermédiaire.

L'intégration devra inclure une fixture PureScript indépendante de Test.LazyEvaluation, les contre-exemples de FunctionFFIBoundary, les régressions de captures/curry/effets, puis une nouvelle régénération et mesure des quatorze benchmarks. Les ~321–324 ms de cette copie ne sont pas une garantie sur le futur code générique.

## Reproduction et état

Depuis la racine PHPurs :

```sh
python3 audit/2026-09-08/r8-root/prepare.py
python3 audit/2026-09-08/r8-root/validate.py
python3 audit/2026-09-08/r8-root/count.py
python3 audit/2026-09-08/r8-root/measure.py
```

`prepare.py` conserve son premier snapshot pour que les relances portent sur les mêmes fichiers. Les grosses copies restent dans `raw/`, ignoré par Git. Les empreintes des variantes sont dans [variants.json](variants.json). Le contrôle final constate les **302 fichiers PHP actifs identiques** au snapshot initial ; les empreintes des modules figurent dans [active-state.json](active-state.json). Aucun rebuild actif, commit ni merge n'a été effectué pendant cette expérience. Les changements préexistants du worktree altbak.pub ont été laissés en place.
