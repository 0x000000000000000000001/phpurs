# R14 — Potentiel des chaînes State immédiatement exécutées

Le 5 octobre 2026. **Étape de mesure terminée ; prototypes sur copies de PHP.**
Le trajet State isolé passe de **500–512 µs** à **1,15–1,17 µs**, pour les mêmes
**1 200 incréments** et le résultat **1 200**. La contribution directe de State
au total témoin est toutefois d'environ **0,72 %**. La reconnaissance générique
dans le compilateur reste une étape distincte.

## Programme et transformations

Le programme étudié est `altbak.pub-phpurs/src/Test/StateMonad.purs` :

```purescript
newtype State s a = State (s -> { val :: a, state :: s })

chainModifications 0 = pureState unit
chainModifications n = bindState (modify (\x -> x + 1))
  \_ -> chainModifications (n - 1)

runManyTimes 0 acc = acc
runManyTimes n acc = runManyTimes (n - 1)
  (acc + (runState (chainModifications 60) 0).state)
```

L'action appelle `Bench.opaque 20`, puis la boucle extérieure. Il s'agit d'une
monade State pure personnalisée : les mesures concernent ses fonctions et ses
records, pas l'élimination d'enveloppes `Effect`.

[`prepare.py`](prepare.py) recopie les CoreFn, puis
[`capture.mjs`](capture.mjs) régénère le programme par le pipeline PHPurs normal
et capture le module optimisé avant génération. Les **306 modules** produisent
exactement les **307 fichiers PHP** actifs. Les prototypes modifient ensuite le
seul site immédiatement consommé de `runManyTimes`, dans `Test.StateMonad` :

1. **Témoin** : PHP généré actuel.
2. **Exécution directe avec records (`strict`)** : un worker reçoit profondeur
   et état, appelle `modify` à arité saturée pour chaque pas, puis poursuit la
   boucle. Il conserve le callback `\x -> x + 1`, le record de `get`, celui de
   `modify` et le record final. Les closures de continuation, les applications
   partielles de `modify` et la closure du cas de base disparaissent.
3. **Projection scalaire (`scalar`)** : le worker exécute chaque `state + 1`
   dans la boucle et retourne directement l'état demandé. Le callback connu et
   les records intermédiaires/final ne sont plus matérialisés.

Les [workers](workers.php) gardent un appel au constructeur original pour une
profondeur négative. La boucle extérieure, l'opaque et l'action Effect sont
identiques. Chaque pas conserve son addition ; aucun `state + depth`, résultat
précalculé ou changement de charge n'est utilisé. Les fonctions publiques
précédant `runManyTimes` restent identiques dans les trois variantes.

Ces modifications sont des substitutions textuelles contrôlées sur le programme
figé. Les noms de ce benchmark servent à localiser l'expérience ; ce code n'est
pas une règle du générateur.

## Mesures temporelles

Six processus frais par mode, ordre symétrique
**témoin / strict / scalaire / scalaire / strict / témoin**. PHP **8.5.4**, JIT
**1255**, buffer **128 MiB**, OPcache CLI actif, cache fichier désactivé, Xdebug
absent. Les processus isolés vérifient l'état effectif du JIT et d'OPcache.

[`measure-isolated.php`](measure-isolated.php) appelle la véritable action
`Test.StateMonad.act`, après chargement de ses dépendances. Il effectue trois
échauffements, calibre un lot d'au moins **20 ms**, puis enregistre **11 lots**
avec `hrtime(true)`. Chaque invocation est vérifiée et son résultat consommé.
Les temps ci-dessous sont ramenés à une action, avec ses 20 chaînes de 60 pas.

| Variante | Médianes des deux processus isolés | Réduction de leur médiane |
| --- | ---: | ---: |
| Témoin | 500,442 / 511,587 µs | — |
| Direct, records conservés | 135,856 / 137,129 µs | 73,03 % |
| Projection scalaire | 1,153 / 1,166 µs | 99,77 % |

La taille PHP totale passe de **7 373 140** à **7 374 070** octets pour `strict`
et **7 374 057** pour `scalar`. Les deux workers diagnostiques sont présents dans
chacune des deux copies ; seul le site d'appel choisit celui de la variante.
Un seul fichier change, sur 307. Les échantillons, tailles, mémoire, état JIT et
empreintes complets figurent dans [`results.json`](results.json).

### Dans la suite complète

L'entrée est le vrai `App/main.mod.php`, avec son échauffement et le protocole
actuel de `Bench.purs` : calibration, meilleur de dix lots. L'oracle du driver
valide les **14 valeurs pour chacun des six processus**.

| Variante | State dans la suite | Total interne des 14 tests |
| --- | ---: | ---: |
| Témoin | 511,592 / 550,962 µs | 73,593 / 73,677 ms |
| Direct, records conservés | 134,405 / 133,383 µs | 69,926 / 69,589 ms |
| Projection scalaire | 1,137 / 1,144 µs | 70,988 / 70,222 ms |

La baisse directement mesurée dans State est d'environ **0,530 ms** pour la
projection scalaire. Les écarts des totaux sont plus grands : RBTree, dont le
PHP est identique, varie de **59,633–60,459 ms** dans les témoins à
**57,561–58,720 ms** dans les copies scalaires. Ces mesures sur machine partagée
ne permettent pas d'attribuer tout le delta global à State. La copie `strict`
a même un total inférieur à `scalar` malgré son noyau State plus lent.

## Comptage du travail

[`count.py`](count.py) instrumente des fichiers séparés, JIT/OPcache désactivés,
et remet les compteurs à zéro après chargement. Les comptes concernent le
module State pour **une action réelle**, pas la totalité du runtime Effect :

| Compteur | Témoin | Direct avec records | Scalaire |
| --- | ---: | ---: | ---: |
| Closures de l'incrément | 1 200 | 1 200 | 0 |
| Closures d'application partielle | 1 200 | 0 | 0 |
| Closures de continuation | 1 200 | 0 | 0 |
| Closures du cas de base | 20 | 0 | 0 |
| Records | 2 420 | 2 420 | 0 |
| Additions du pas | 1 200 | 1 200 | 1 200 |
| Additions de l'accumulateur extérieur | 20 | 20 | 20 |

Le commentaire source « 1 200 closures » décrit les pas logiques : le PHP
actuel en crée réellement **3 620** dans ce module. Les records sont ceux de
`get` et `modify` à chaque pas, puis un record terminal par chaîne. Le premier
prototype isole donc une grande partie du coût des continuations/currying ;
le second retire aussi le callback connu et les records.

## TAST et conditions d'une future intégration

[`inspect.py`](inspect.py) vérifie les corps optimisés **de ce programme figé**,
après effacement des enveloppes `Typed`/`TypeApp` pour la lecture structurelle :

- `chainModifications` est seul dans son groupe récursif. Sa signature aplatie
  est `(Int, Int) -> { val :: Unit, state :: Int }`, mais son **arité runtime est
  un** : il retourne une fonction de l'état. Cette distinction exclut une simple
  adaptation de la preuve actuelle de `ThunkFusion`.
- Le cas zéro retourne la closure construisant `{ val: unit, state: s }`.
  L'autre branche prépare `modify` avec un callback entier fermé `x + 1`, puis
  retourne une closure qui appelle le constructeur à `n - 1`, avec l'état
  provenant de la modification précédente.
- `modify` est instancié à `Int` via `TypeApp`. Son corps optimisé appelle encore
  `get`, projette `val`, appelle le callback et construit le résultat. La preuve
  du seul constructeur doit donc être complétée par celle de ces helpers.
- Le site consommateur est une application complète `chainModifications 60 0`,
  immédiatement projetée sur `state`. L'accessor lui-même n'a pas d'enveloppe
  `Typed` ; le layout vient de la signature du producteur et de ses corps.
- Certaines annotations internes conservent `a$scope11` ou `b$scope23`, alors
  que la signature externe est concrète. Les corps et le `Unit` canonique
  fournissent les faits nécessaires ici ; un type interne isolé ne suffit pas.

Une passe générique devra prouver ces relations indépendamment des noms, borner
les parcours et les copies, et produire des workers privés frais. Elle devra
vérifier que le record supprimé et son champ `val` sont purement intermédiaires,
et conserver les expressions susceptibles d'effets ou d'exceptions. Les States
conservés, callbacks inconnus, sorties de records, types non établis et chemins
FFI non prouvés demandent un refus ou une preuve distincte. Un éventuel garde
dynamique doit préserver le repli négatif et rester idempotent au rescan.

## Validation sémantique

[`validate.php`](validate.php) fournit :

- **35 combinaisons** profondeur/état, comparées à chacun des deux workers :
  **70 comparaisons** exactes de valeur et de type PHP, dont zéro, profondeur
  2 048 et état proche de `PHP_INT_MAX` ;
- ordre et unicité d'évaluation des arguments ;
- State public conservé et exécuté plusieurs fois, vrais objets `Closure`,
  records publics frais et frontières `Foreign.typeOf` / `tagOf` / paramètre
  PHP typé `Closure` ;
- callback inconnu différé, lecture d'une capture modifiée entre deux runs,
  identité des exceptions, valeur `val` elle-même invocable ;
- ordre du bind et transmission séparée de `val` et `state` ;
- deux replis négatifs instrumentés, avec conservation des arguments et de
  l'exception sentinelle. La chaîne négative réellement divergente n'est pas
  exécutée ;
- contre-exemple d'inspection de pile : le callback observe deux frames via
  `modify`, contre une lors de l'appel direct ; son type scalaire ne prouve pas
  l'inlining sûr ;
- contre-exemple numérique : après 2 048 incréments près de `PHP_INT_MAX`, le
  résultat PHP diffère de l'addition unique `initial + 2048`.

## Décision et reproduction

Le potentiel local est établi. Son gain direct dans cette suite est d'environ
une demi-milliseconde, sur une part State de **0,72 %**. La prochaine micro-étape
du backlog reste la mesure des callbacks de parcours, avant de choisir la
prochaine intégration runtime. Cette campagne n'ajoute pas une passe au compilateur.

Depuis le checkout PHPurs, avec un nouveau dossier d'artefacts :

```sh
python3 audit/2026-10-05/state-chains/prepare.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-05/state-chains/inspect.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-05/state-chains/measure.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-05/state-chains/count.py --artifacts "$ARTIFACTS"
python3 audit/2026-10-05/state-chains/summarize.py --artifacts "$ARTIFACTS"
```

Préparation attendue : modules JS du compilateur construits et workspace
`altbak.pub-phpurs/run/bak/php/modes/pure` généré avec ses dépendances Composer
verrouillées. Toute différence de PHP entre le témoin régénéré et les sorties
actives interrompt la préparation. La forme des corps optimisés est également
vérifiée avant de retenir ce diagnostic.

Le contrôle final constate **1 143 fichiers d'entrée inchangés**, **307 fichiers
PHP actifs inchangés**, les empreintes des trois variantes mesurées et celles du
vendor identiques. Seuls cet audit et le suivi du backlog sont ajoutés/modifiés.
Révisions au début de la campagne : PHPurs `e604d04c87f0df1242673e3631a70c796ab41b19`, PBO
`157a544f0a469c7b0137a3fca66e626d717db493`, benchmarks
`be2b32d564a72fbafc429373697872b0a66e3c4c`.

Artefacts complets :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-r14-state-chains-20261005`.
