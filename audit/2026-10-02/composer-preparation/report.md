# Nettoyage PHPurs et préparation Composer — 2 octobre 2026

## Nettoyage

Suppression des espaces temporaires du dépôt `phpurs/phpurs`, après vérification
de leur rôle et des références :

| Ensemble | Entrées supprimées | Octets logiques |
| --- | ---: | ---: |
| 17 espaces `audit/**/raw` | 70 534 | 1 636 323 288 |
| `output.bak` | 3 007 | 262 065 072 |
| `output-es` | 505 | 3 116 543 |
| Produits/essais temporaires de `tests/runner` | 6 027 | 101 239 851 |

Avec les fichiers `.DS_Store` restants : **80 077 entrées et 2 002 783 682 octets**
retirés (2,00 Go, soit 1,87 Gio ; les liens sont comptés sans suivre leurs cibles).
S'y ajoutent les anciens patches de débogage, `.orig`/`.rej`, `patch.diff` et le
module `Phpurs.Mtime`, dont aucun import/utilisateur ne subsistait, ainsi que ses
sorties compilées résiduelles.

Les 1 800 fichiers suivis restants du runner sont supprimés ; seuls `spago.yaml`
et `spago.lock` constituent sa configuration durable. `.gitignore` couvre ce
scratch, les produits bruts d'audit et les caches. Les rapports, scripts et
résultats compacts des audits restent les traces des campagnes historiques.

Validation : `npm run build` réussit sans avertissement/erreur ; les **61 tests
codegen** passent. Après suppression complète du scratch, `./bin/test TCO.purs
FunctionFFIBoundary.purs` reconstruit ses dépendances et réussit les deux fixtures.
Les fichiers temporaires produits par ce contrôle ont ensuite été retirés.
Le bundle garde son SHA-256
`6f4d128fbc2cb59b9ad820c6bf1c4fa4d5ffb84191b076ef9ddb09b1f61a9d51`.

## Dernier point B3

Le checkout `altbak.pub-phpurs` utilisait déjà `composer install` et proposait
`--build-only`/`--run-only`. La modification porte sur `bin/php/driver.py` et le
petit `bin/php/composer-state.php` :

- installation depuis le lock uniquement, sans plugins/scripts, avant les
  contrôles de code généré et l'exécution ;
- refus d'un lock absent, périmé pour le manifeste racine, ou incompatible avec
  les exigences du paquet `phpurs/lib-deps` réellement généré ;
- utilisation du calcul `content-hash` et de la détection de plateforme du PHAR
  Composer épinglé, via une sonde PHP sans lancement de l'installateur ;
- clé de préparation comprenant manifestes, lock, code des outils, exécutable
  PHP, extensions/bibliothèques, configuration et environnement Composer ; les
  réglages susceptibles de contenir des secrets ne sont conservés que hachés ;
- vérification des octets de `vendor` et du lien du paquet vers l'`output`
  courant ; le contenu PHP généré n'est pas copié dans le cache de dépendances ;
- invalidation de l'état avant toute réinstallation ; publication atomique de
  `.composer-prepared.json` après succès et relecture des entrées ;
- état `installed`/`reused` et commandes réellement lancées dans le manifeste de
  build ; `--run-only` vérifie les fichiers et les liens avant d'exécuter PHP.

Une reconstruction PHP avec les mêmes exigences conserve ainsi les fichiers et
dates de `vendor`. Une altération réinstalle le répertoire de dépendances du
workspace isolé : `install` seul ne répare pas tous les fichiers de paquets
modifiés. Le lock du dépôt demeure inchangé. La procédure de renouvellement
explicite du lock figure dans
[`docs/benchmark-methodology.md`](../../../../../altbak.pub-phpurs/docs/benchmark-methodology.md#php-dependency-preparation).

## Contrôles

`python3 -B test/php-composer.py -v` : **six tests**, avec le vrai Composer et un
graphe local sans réseau. Ils couvrent première préparation, réutilisation sans
installateur, sortie PHP recréée, conservation des dates, fichiers supprimés,
modifiés ou ajoutés, lien remplacé, lock absent/périmé, exigences modifiées,
nouveau lock explicite, état corrompu, configuration globale changée et
installation interrompue. Les huit contrôles du validateur de benchmark passent
aussi ; `php -l` et les vérifications de whitespace réussissent.

Le témoin utilise une copie figée du driver précédent, avec sa racine ajustée
vers le même checkout. Dans un workspace temporaire unique, quatre exécutions
de la suite pure sont validées : témoin, première préparation, reconstruction
avec réutilisation, puis `--run-only`. À chaque étape :

- **350 artefacts identiques octet pour octet** au témoin : 307 fichiers PHP
  générés, leur manifeste Composer, 40 fichiers `vendor`, manifeste racine et
  lock ;
- **14 résultats identiques et validés** ;
- zéro commande d'installation pour la reconstruction réutilisée et l'exécution
  seule ; dates de tous les fichiers/liens/répertoires `vendor` conservées ;
- les contrôles du véritable ABI PHP généré passent pendant les builds.

Un essai additionnel remplace le lien `vendor/phpurs/lib-deps` après le build :
`--run-only` échoue avant toute exécution du programme ; le lien est rétabli.
Les durées de processus isolées conservées dans `results.json` servent à tracer
les essais ; elles ne constituent pas une campagne de mesure de gain runtime ou
de compilation. La réduction démontrée est le passage de **une à zéro commande
Composer install** pour un build à dépendances inchangées.

Traces locales :
`/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b3-composer-k16e_zbp`.
Le manifeste détaillé du nettoyage est dans
`.../phpurs-cleanup-uvfa_i7v/removed.json`. Les résultats compacts et identités
sont conservés dans [results.json](results.json).

HEADs observés : benchmarks `25950a31bb0c76aeab549c843479cded92a29b1b` ; PHPurs
`1bc5ec3` après les commits externes `3262885` et `1bc5ec3` reçus pendant ce travail.
Aucun commit n'a été créé par l'assistant pour ce lot.
