# Tag privé supprimé : intégration et mesures

La passe `EnumRegions` émet des constructeurs privés dont la propriété `$tag`
n'était jamais lue. Le générateur la supprime pour ces constructeurs. Mesure
appariée sur le même TAST : **≈ 3,6 % du total**, l'essentiel venant de RBTree.

## Mécanisme

`CodeGen.translate` émettait pour chaque constructeur :

    final class Mod_Ctor { public $tag = 'Ctor'; public function __construct(...) {} }

Les correspondances de motifs générées passent par `OpIsTag`, que
`translateOperator1` traduit en `instanceof` ; aucune lecture de `->{'tag'}`
n'apparaît dans les sorties. Les énumérations des régions prouvées sont
abaissées en entiers et les constructeurs privés ne franchissent ni frontière
FFI ni frontière publique. Seule la représentation publique garde son `$tag`.

Le changement est local à `translate` : `tagDecl` reste vide pour un
constructeur membre de `regions.privateConstructors`.

## Résultat négatif conservé

Typer les propriétés promues des classes privées (`int $value0`, `int $value2`)
est nettement plus lent, conformément au commentaire existant : 150–155 ms
contre 113–117 ms sur RBTree isolé, soit +30 à 35 %. La mesure B/D ci-dessous
le documente ; les champs privés restent non typés.

## Mesures

Machine Apple M4 Pro, PHP 8.5.4, OPcache CLI, JIT 1255, buffer 128 MiB, Xdebug
désactivé. Le TAST est figé : chaque variante ne diffère que par la ligne de
classe privée du module `Test.RBTree`.

### RBTree isolé (copies du PHP généré)

| Tour | A référence | B champs typés | C sans tag | D typés + sans tag |
| --- | ---: | ---: | ---: | ---: |
| 1 | 116,671 | 152,352 | 111,521 | 144,573 |
| 2 (ordre inverse) | 113,498 | 152,332 | 111,198 | 144,484 |
| 3 | 115,921 | 154,963 | 112,473 | 149,780 |
| 4 (ordre inverse) | 114,933 | 150,318 | 107,566 | 144,850 |

Médiane de douze mesures par tour, en millisecondes. C est systématiquement
sous A (environ 4 %) ; B et D sont nettement au-dessus.

### Programme complet (même TAST, même PBO)

`run-full.sh` restitue ou retire uniquement la déclaration de `$tag` :

| Variante | Total affiché |
| --- | ---: |
| sans tag | 116,644 ms |
| sans tag | 115,189 ms |
| avec tag | 120,374 ms |
| avec tag | 120,089 ms |

Soit **4,3 ms / 3,6 %** sur le total, l'essentiel venant de RBTree. Les quatorze
résultats sont vérifiés par le protocole lui-même dans les deux variantes.

Parmi les modules qui contiennent un constructeur privé, seul
`Test.RBTree/index.php` change. Les builds FFI contiennent eux aussi
`Test.RBTree` (compilé mais non importé par `AppFFI`/`AppFFICheatcode`, qui
passent par `Test.RBTreeFFI`) : le retrait du tag n'y touche aucun module
exécuté par les programmes FFI.

## Validation

- Neuf suites `tests/codegen`, dont deux nouvelles assertions dans
  `enum-regions.mjs` : la classe privée ne porte pas `$tag`, la classe publique
  le conserve.
- Aucune lecture `->{'tag'}` dans l'ensemble du PHP généré.
- Programme complet : code 0 et quatorze résultats dans les deux variantes.
- Le préfixe public de RBTree (classes R/B/E/T, fonctions exportées) est
  inchangé.

## Identification de l'outillage et suite

Les campagnes publiées ont été mesurées avec le bundle `74124d39`. Le `runp -c`
de 15:00 a reconstruit `6c2efa09` après des modifications concurrentes du fork
PBO (`Monomorphize.purs` réécrit à 15:00:04) ; la présente intégration
correspond au bundle `86ad0c0b`. La colonne compilée du README est republiée
depuis la campagne `var/benchmark/php-pure-20260924d` (116,59 ms). Les colonnes
FFI sont conservées après vérification ABBA : l'ancien build publié et le
nouveau mesurent la même chose sous la fenêtre courante, et l'ancien build y
donne lui aussi 211,9–215,3 ms contre 202,55 ms publiés, ce qui identifie la
fenêtre chargée plutôt qu'une régression. Voir
`altbak.pub/docs/benchmark-results/2026-09-24-php-private-tag.md`.

## Reproduction

- `run-micro.sh <workspace>` : variantes A–D sur RBTree isolé.
- `run-full.sh <workspace>` : programme complet avec/sans tag, même TAST.
- `bench-rbtree.php` : chrono isolé du seul `act`.
